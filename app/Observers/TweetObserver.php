<?php

namespace App\Observers;

use App\Models\Tweet;
use App\Models\Sentiment;

class TweetObserver
{
    public function created(Tweet $tweet): void
    {
        if ($tweet->type == 'sentiment') {
            $tweet->sentiment()->save(new Sentiment());
        }
    }
}
