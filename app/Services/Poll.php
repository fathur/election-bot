<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Services\Twitter\Twitter;
use App\Services\Twitter\QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\PollChoice;
use App\Models\Account;
use App\Models\Tweet;

class Poll
{
    public const SEARCH_LAST_MINUTES = 30;
    public const POLL_DURATION_MINUTES = 24 * 60;

    private $enableLookupTweet = true;
    private $candidates;
    private $twitter;

    public function __construct()
    {
        $this->candidates = PollChoice::all();
        $this->twitter = new Twitter;
        $this->me = Cache::remember(QueryBuilder::CURRENT_USER_CACHE_KEY, now()->addMonth(), function() {
            return $this->twitter->getMe()->data;
        });

    }

    public static function run(string $target)
    {
        Log::info("Poll::run() running...");

        $instance = new self;
        $instance->execute(query: Twitter::queryFor($target));

        Log::info("Poll::run() ran!");
    }

    public function execute($query)
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
                $this->processTweets($response);
            }
            if (property_exists($meta, 'next_token')) {
                $nextToken = $meta->next_token;
            } else {
                $loop = false;
            }
        }

        Log::info("Query `{$query}` executed!");

    }

    public function processTweets($response)
    {
        Log::info("Tweet processing...");

        foreach ($response->data as $twitterTweet) {
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
Siapakah calon presiden pilihanmu di 2024? "
Vote sebagai bentuk kepedulianmu terhadap pemilu ini! \n\n"
Retweet untuk menyebarkan, dan beri 🧡 jika bermanfaat.
TXT;

        $twitterTweet = $this->twitter->createTweet(
            inReplyToTweetId: $tweet->twitter_id,
            text: $text,
            pollOptions: $this->shuffleCandidates(),
            pollDurationMinutes: self::POLL_DURATION_MINUTES
        );

        $this->storeToDatabase($tweet, $twitterTweet);

        $url = "https://twitter.com/{$this->me->username}/status/{$twitterTweet->data->id}";
        Log::info("Poll posted in {$url}!");

    }

    public function storeToDatabase(Tweet $tweet, $twitterTweet)
    {
        $account = Account::where('username', $this->me->username)->first();
        $pollTweet = $account->tweets()->create([
            'twitter_id' => $twitterTweet->data->id,
            'parent_id' => $tweet->id,
            'url'   => "https://twitter.com/{$this->me->username}/status/{$twitterTweet->data->id}",
            'text'  => $twitterTweet->data->text,
            'type'  => 'poll'
        ]);

        // Store the poll option to pivot table
        $pollTweet->poll()->save(new \App\Models\Poll([
            'start_at'  => now(),
            'end_at'    => now()->addMinutes(self::POLL_DURATION_MINUTES)
        ]));
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
