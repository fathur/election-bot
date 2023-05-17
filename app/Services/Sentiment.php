<?php

namespace App\Services;

use App\Services\Twitter\Twitter;
use Illuminate\Support\Facades\Log;

class Sentiment {

    const SEARCH_LAST_MINUTES = 60 * 24;
    const MAX_TWITTER_API_CALL = 100;
    
    private $twitter;

    public function __construct()
    {
        $this->twitter = new Twitter();
    }

    public static function fetchTweets()
    {
        (new self)->getTweets();
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
                expansions: 'author_id',
                maxResults: 100
            );
            $i++;
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

            if ($i >= self::MAX_TWITTER_API_CALL) {
                $loop = false;
            }
        }
    }

    public function processTweets($response)
    {
        Log::info(json_encode($response->data));
    }
}