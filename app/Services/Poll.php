<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Services\Twitter\Twitter;
use App\Services\Twitter\QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\PollChoice;
use App\Models\Country;
use App\Models\Account;
use App\Models\Tweet;

class Poll
{
    public const SEARCH_LAST_MINUTES = 30;
    public const POLL_DURATION_MINUTES = 24 * 60;

    private $enableLookupTweet = true;
    private $candidates;
    private $twitter;
    private $countries;

    protected $me;

    public function __construct()
    {
        $this->candidates = PollChoice::all();
        $this->twitter = new Twitter();
        $this->me = Cache::remember(QueryBuilder::CURRENT_USER_CACHE_KEY, now()->addMonth(), function () {
            $data = $this->twitter->getMe()->data;
            $exists = Account::where('twitter_id', $data->id)->exists();
            if (!$exists) {
                Account::create([
                    'twitter_id'    => $data->id,
                    'username'    => $data->username,
                    'name'    => $data->name,
                ]);
            }
            return $data;
        });

        $this->countries = Country::all();

    }

    public static function run(string $target)
    {
        Log::info("Poll::run() running...");

        if ($target == 'candidate') {
            $shouldFilterText = false;
        } else {
            $shouldFilterText = true;
        }

        $instance = new self();
        $instance->execute(
            query: Twitter::queryFor($target),
            shouldFilterText: $shouldFilterText
        );

        Log::info("Poll::run() ran!");
    }

    public function execute($query, $shouldFilterText = true)
    {
        Log::info("Query `{$query}` executing...");

        $loop = true;
        $nextToken = null;
        while ($loop) {

            Log::info("Tweets searching...");
            $response = $this->twitter->searchRecentTweets(
                query: $query,
                startAt: now()->subMinutes(self::SEARCH_LAST_MINUTES),
                endAt: now()->subSeconds(15),
                sortOrder: 'recency',
                nextToken: $nextToken,
                userFields: 'id,username,name',
                expansions: 'author_id',
                maxResults: 100
            );
            Log::info("Tweets searched! Endpoint hit!");

            $meta = $response->meta;
            if ($meta->result_count == 0) {
                Log::warning("No data for tweets search!");
                $loop = false;
                continue;
            }

            $data = $response->data;

            if ($data) {
                $this->processTweets($response, $shouldFilterText);
            }
            if (property_exists($meta, 'next_token')) {
                $nextToken = $meta->next_token;
            } else {
                $loop = false;
            }
        }

        Log::info("Query `{$query}` executed!");

    }

    protected function isPassTextFilter(string $text): bool
    {
        $isPassed = true;

        # Filter international country name
        $countries = $this->countries->pluck('name')->map(function ($country) {
            return strtolower($country);
        })->all();


        foreach ($countries as $country) {
            if (stripos($text, $country) !== false) {  //
                $isPassed = false;
                break;
            }
        }

        if ($isPassed === false) {
            return $isPassed;
        }


        # Filter indonesia locale country name
        $countries = $this->countries->pluck('locale_name')->map(function ($country) {
            return json_decode($country)->id;
        })->unique()->reject('')->all();


        foreach ($countries as $country) {
            if (stripos($text, $country) !== false) {  //
                $isPassed = false;
                break;
            }
        }

        if ($isPassed === false) {
            return $isPassed;
        }


        # Filter specific keywords
        $caseSensitiveKeywords = ['DPR', 'DPD'];
        foreach ($caseSensitiveKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {  //
                $isPassed = false;
                break;
            }
        }
        if ($isPassed === false) {
            return $isPassed;
        }


        $caseInsensitiveKeywords = ['caleg', 'pileg', 'bacaleg', 'legislatif', 'pilkada'];
        foreach ($caseInsensitiveKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {  //
                $isPassed = false;
                break;
            }
        }

        if ($isPassed === false) {
            return $isPassed;
        }


        return $isPassed;
    }

    public function processTweets($response, $shouldFilterText = true)
    {
        Log::info("Tweet processing...");

        foreach ($response->data as $twitterTweet) {

            if ($shouldFilterText && !$this->isPassTextFilter($twitterTweet->text)) {
                Log::warning([
                    "message" => "Not pass filter",
                    "text" => $twitterTweet->text
                ]);
                continue;
            }

            // This is the original tweet, either from candidates, media, or parties.
            $tweet = Tweet::where('twitter_id', $twitterTweet->id)->first();
            if (!$tweet) {
                $account = $this->getAccount($response, $twitterTweet);
                $tweet = $account->tweets()->create([
                    'twitter_id' => $twitterTweet->id,
                    'text' => $twitterTweet->text,
                    'url' => "https://twitter.com/{$account->username}/status/{$twitterTweet->id}",
                    'type'  => 'text'
                ]);
            }

            if ($tweet->hasPoll()) {
                continue;
            }

            $this->postPoll($tweet);
        }

        Log::info("Tweet processed!");

    }

    public function getAccount($response, $twitterTweet)
    {
        $authorId = $twitterTweet->author_id;

        $users = collect($response->includes->users);
        $author = $users->where('id', $authorId)->first();

        $authorUsername = $author->username;
        $authorName = $author->name;

        $account = Account::where('twitter_id', $authorId)->first();
        if ($account) {
            return $account;
        }

        return Account::create([
            'twitter_id' => $authorId,
            'username' => $authorUsername,
            'name' => $authorName
        ]);
    }

    public function postPoll(Tweet $tweet)
    {
        Log::info("Poll posting...");

        $text = <<<TXT
Siapakah calon presiden pilihanmu di 2024?
Vote sebagai bentuk kepedulianmu terhadap pemilu ini! \n\n
Retweet, like, dan reply untuk mendukung polling ini. Follow akun kami untuk mendapatkan agregat laporan. 🦾

#prabowo #anies #ganjar #pilpres
TXT;

        // Bot tweet
        $twitterTweet = $this->twitter->createTweet(
            inReplyToTweetId: $tweet->twitter_id,
            text: $text,
            pollOptions: $this->shuffleCandidates(),
            pollDurationMinutes: self::POLL_DURATION_MINUTES
        );

        $db = $this->storeToDatabase($tweet, $twitterTweet);

        $url = "https://twitter.com/{$this->me->username}/status/{$twitterTweet->data->id}";
        Log::info("Poll posted in {$url}!");

    }

    public function storeToDatabase(Tweet $parentTweet, $twitterTweet)
    {
        $account = Account::where('username', $this->me->username)->first();

        $pollTweet = $account->tweets()->create([
            'twitter_id' => $twitterTweet->data->id,
            'parent_id' => $parentTweet->id,
            'url'   => "https://twitter.com/{$account->username}/status/{$twitterTweet->data->id}",
            'text'  => $twitterTweet->data->text,
            'type'  => 'poll'
        ]);

        // Store the poll option to pivot table
        $poll = $pollTweet->poll()->save(new \App\Models\Poll([
            'start_at'  => now(),
            'end_at'    => now()->addMinutes(self::POLL_DURATION_MINUTES)
        ]));

        return [
            'account'   => $account,
            'tweet'     => $pollTweet,
            'poll'      => $poll
        ];
    }

    public function shuffleCandidates()
    {
        $options = [];

        $realCandidates = $this->candidates
            ->where('is_considered', true)
            ->shuffle()
            ->pluck('option')
            ->all();

        $options = $realCandidates;

        $noCandidate = $this->candidates
            ->where('is_considered', false)
            ->pluck('option')
            ->first();

        array_push($options, $noCandidate);

        return $options;
    }
}
