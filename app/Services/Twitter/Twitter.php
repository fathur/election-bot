<?php

namespace App\Services\Twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Carbon;

class Twitter
{
    protected $client;

    public function __construct()
    {
        $this->client = new TwitterOAuth(
            config('services.twitter.api_key'),
            config('services.twitter.api_key_secret'),
            config('services.twitter.access_token'),
            config('services.twitter.access_token_secret'),
        );
        $this->client->setApiVersion('2');
    }

    public static function queryFor(string $target)
    {
        return QueryBuilder::for($target);
    }

    public function searchRecentTweets(
        $query,
        $startAt = null,
        $endAt = null,
        $sortOrder = 'recency',
        $nextToken = null,
        $userFields = null,
        $expansions = null,
        $maxResults = 10,
    ) {
        $parameters = [
            'query' => $query,
            'max_results' => $maxResults,
            'sort_order' => $sortOrder
        ];

        if ($startAt != null) {
            if ($startAt instanceof Carbon) {
                $startAt = $startAt->toIso8601String();
            }

            $parameters['start_time'] = $startAt;
        }

        if ($endAt != null) {
            if ($endAt instanceof Carbon) {
                $endAt = $endAt->toIso8601String();
            }

            $parameters['end_time'] = $endAt;
        }

        if ($nextToken != null) {
            $parameters['next_token'] = $nextToken;
        }

        if ($expansions != null) {
            $parameters['expansions'] = $expansions;
        }

        if ($userFields != null) {
            $parameters['user.fields'] = $userFields;
        }

        $response = $this->client->get('tweets/search/recent', $parameters);
        Log::info([
            "mark" =>  "Searh recent tweets",
            "parameters" => json_encode($parameters),
            "response" => json_encode($response)
        ]);
        return $response;
    }

    public function createTweet(
        $text,
        $inReplyToTweetId = null,
        $pollOptions = [],
        $pollDurationMinutes = null
    ) {

        $parameters = [
            'text' => $text
        ];

        $reply = [];
        $poll = [];

        if ($inReplyToTweetId != null) {
            $reply['in_reply_to_tweet_id'] = $inReplyToTweetId;
        }

        if ($pollOptions != []) {
            $poll['options'] = $pollOptions;

        }


        if ($pollDurationMinutes != null) {
            $poll['duration_minutes'] = $pollDurationMinutes;
        }

        if ($poll != []) {
            $parameters['poll'] = $poll;
        }

        if ($reply != []) {
            $parameters['reply'] = $reply;
        }


        $response = $this->client->post('tweets', $parameters, true);
        Log::info([
            "mark" =>  "Create new tweet",
            "parameters" => json_encode($parameters),
            "response" => json_encode($response)
        ]);
        return $response;
    }

    public function getTweets(
        $ids,
        $expansions = null,
        $pollFields = null,
        $tweetFields = null,
        $userFields = null,
    ) {
        $parameters = [
            'ids' => $ids
        ];

        if ($expansions != null) {
            $parameters['expansions'] = $expansions;
        }

        if ($pollFields != null) {
            $parameters['poll.fields'] = $pollFields;
        }

        if ($tweetFields != null) {
            $parameters['tweet.fields'] = $tweetFields;
        }

        if ($userFields != null) {
            $parameters['user.fields'] = $userFields;
        }


        $response = $this->client->get("tweets", $parameters);
        Log::info([
            "mark" =>  "Get one tweets",
            "ids" => json_encode($ids),
            "parameters" => json_encode($parameters),
            "response" => json_encode($response)
        ]);
        return $response;
    }

    public function getTweet(
        $id,
        $expansions = null,
        $pollFields = null,
        $tweetFields = null,
        $userFields = null,
    ) {
        $parameters = [];

        if ($expansions != null) {
            $parameters['expansions'] = $expansions;
        }

        if ($pollFields != null) {
            $parameters['poll.fields'] = $pollFields;
        }

        if ($tweetFields != null) {
            $parameters['tweet.fields'] = $tweetFields;
        }

        if ($userFields != null) {
            $parameters['user.fields'] = $userFields;
        }


        $response = $this->client->get("tweets/{$id}", $parameters);
        Log::info([
            "mark" =>  "Get one tweet",
            "id" => $id,
            "parameters" => json_encode($parameters),
            "response" => json_encode($response)
        ]);
        return $response;
    }

    public function getMe()
    {
        return $this->client->get('users/me', [
            'user.fields' => 'id,name,username'
        ]);
    }
}
