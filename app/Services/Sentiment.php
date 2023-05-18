<?php

namespace App\Services;

use App\Services\Twitter\Twitter;
use Illuminate\Support\Facades\Log;
use App\Models\{
    Account,
    Tweet
};

class Sentiment
{
    public const SEARCH_LAST_MINUTES = 60 * 24 * 7;
    public const MAX_TWITTER_API_CALL = 1;

    private $twitter;

    public function __construct()
    {
        $this->twitter = new Twitter();
    }

    public static function fetchTweets()
    {
        (new self())->getTweets();
    }

    public function getTweets()
    {
        $loop = true;
        $nextToken = null;
        $i = 0;
        while ($loop) {

            Log::info("Tweets searching...");
            $response = $this->twitter->searchRecentTweets(
                query: Twitter::queryFor('sentiment'),
                startAt: now()->subMinutes(self::SEARCH_LAST_MINUTES),
                endAt: now()->subSeconds(15),
                sortOrder: 'recency',
                nextToken: $nextToken,
                userFields: 'id,username,name',
                tweetFields: 'author_id,in_reply_to_user_id,public_metrics,referenced_tweets,conversation_id',
                expansions: 'author_id,in_reply_to_user_id,referenced_tweets.id,referenced_tweets.id.author_id',
                maxResults: 10
            );
            $i++;
            Log::info("Tweets searched! Endpoint hit!");


            Log::info(json_encode($response));

            $data = $response->data;

            if ($data) {
                $this->processTweets($response);
            }

            if (!property_exists($response, 'meta')) {
                Log::warning("No meta property");
                $loop = false;
                continue;
            }

            $meta = $response->meta;
            if ($meta->result_count == 0) {
                Log::warning("No data for tweets search!");
                $loop = false;
                continue;
            }

            if (property_exists($meta, 'next_token')) {
                $nextToken = $meta->next_token;
            } else {
                $loop = false;
            }

            if ($i >= self::MAX_TWITTER_API_CALL) {
                $loop = false;
            }
        }
    }

    public function processTweets($response)
    {
        Log::info(json_encode($response));

        $twitterTweets = $response->data;
        $meta = $response->meta;
        $includes = $response->includes;

        $users = collect($includes->users);
        foreach ($twitterTweets as $twitterTweet) {
            Log::info("Processing {$twitterTweet->id}");

            $user = $users->where('id', $twitterTweet->author_id)->first();

            $account = Account::where('twitter_id', $twitterTweet->author_id)->first();
            if (!$account) {
                $account = Account::create([
                    'twitter_id' => $user->id,
                    'username'  => $user->username,
                    'name'  => $user->name
                ]);
            }

            $tweet = $account->tweets()->where('twitter_id', $twitterTweet->id)->exists();
            if ($tweet) {
                continue;
            }

            $account->tweets()->create([
                'type'  => 'sentiment',
                'text'  => $twitterTweet->text,
                'twitter_id'   => $twitterTweet->id,
                'url'   => "https://twitter.com/{$user->username}/status/{$twitterTweet->id}"
            ]);

        }

    }
}
