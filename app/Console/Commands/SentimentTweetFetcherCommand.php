<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Sentiment;

class SentimentTweetFetcherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentiment:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the public data for sentiment analysis.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // In a month have 10.000 capacity. Not clear what is 10.000 means. It can be endpoint call, or tweets count.
        Sentiment::fetchTweets();
    }
}
