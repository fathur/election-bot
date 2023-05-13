<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Twitter\QueryBuilder;

class AlphaMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alpha:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate old structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $this->migrateChoices();
        // $this->migrateAccounts();
        // $this->migrateTweets();
        // $this->migratePolls();
        $this->migratePollResults();
    }

    protected function migrateChoices()
    {
        $this->info('Poll choice migrating...');
        $alphaChoices = \App\Models\Alpha\PollChoice::all();
        foreach ($alphaChoices as $alphaChoice) {
            $exists = \App\Models\PollChoice::where('option', $alphaChoice->option)->exists();
            if ($exists) {
                $choice = \App\Models\PollChoice::where('option', $alphaChoice->option)->first();
                $choice->alpha_poll_choice_id = $alphaChoice->id;
                $choice->save();
            } else {
                \App\Models\PollChoice::create([
                    'option' =>  $alphaChoice->option,
                    'alpha_poll_choice_id' => $alphaChoice->id,
                ]);
            }
        }
        $this->info('Poll choice migrated!');

    }

    protected function migrateAccounts()
    {
        $this->info('Account migrating...');
        $alphaAccounts = \App\Models\Alpha\Account::all();
        foreach ($alphaAccounts as $alphaAccount) {

            $exists = \App\Models\Account::where('twitter_id', $alphaAccount->object_id)->exists();
            if ($exists) {
                $account = \App\Models\Account::where('twitter_id', $alphaAccount->object_id)->first();
                $account->alpha_account_id = $alphaAccount->id;
                $account->save();
            } else {
                \App\Models\Account::create([
                    'twitter_id' => $alphaAccount->object_id,
                    'username' => $alphaAccount->username,
                    'name' => $alphaAccount->name,
                    'alpha_account_id' => $alphaAccount->id
                ]);
            }
        }
        $this->info('Account migrated!');

    }

    protected function migrateTweets()
    {
        $this->info('Tweet migrating...');

        $alphaTweets = \App\Models\Alpha\Tweet::all();
        foreach ($alphaTweets as $alphaTweet) {
            $exists = \App\Models\Tweet::where('twitter_id', $alphaTweet->object_id)->exists();
            if ($exists) {
                $tweet = \App\Models\Tweet::where('twitter_id', $alphaTweet->object_id)->first();
                $tweet->alpha_tweet_id = $alphaTweet->id;
                $tweet->save();
            } else {
                \App\Models\Tweet::create([
                    'twitter_id' => $alphaTweet->object_id,
                    'alpha_tweet_id' => $alphaTweet->id,
                    'account_id'    => \App\Models\Account::where('alpha_account_id', $alphaTweet->account_id)->first()->id,
                    'url'   => $alphaTweet->url,
                    'text'   => $alphaTweet->text,
                    'type'  => 'text'
                ]);
            }
        }
        $this->info('Tweet migrated!');


    }

    protected function migratePolls()
    {
        $this->info('Poll migrating...');

        $alphaPolls = \App\Models\Alpha\Poll::all();
        foreach ($alphaPolls as $alphaPoll) {
            $exists = \App\Models\Tweet::where('twitter_id', $alphaPoll->object_id)->exists();
            if ($exists) {
                $tweet = \App\Models\Tweet::where('twitter_id', $alphaPoll->object_id)->first();
                $tweet->alpha_poll_tweet_id = $alphaPoll->id;
                $tweet->parent_id = \App\Models\Tweet::where('twitter_id', $alphaPoll->tweet_id)->first()->id;
                $tweet->save();
            } else {
                $me = Cache::get(QueryBuilder::CURRENT_USER_CACHE_KEY);
                if ($me == null) {
                    throw new PollBotException("My cache not found");

                }

                if (!\App\Models\Account::where('username', $me->username)->exists()) {
                    \App\Models\Account::create([
                        'username'  => $me->username,
                        'name'  => $me->name,
                        'twitter_id'  => $me->id,
                    ]);
                }

                $tweet = \App\Models\Tweet::create([
                    'twitter_id' => $alphaPoll->object_id,
                    'parent_id' => \App\Models\Tweet::where('alpha_tweet_id', $alphaPoll->tweet_id)->first()->id,
                    'alpha_poll_tweet_id' => $alphaPoll->id,
                    'account_id'    => \App\Models\Account::where('username', $me->username)->first()->id,
                    'url'   => $alphaPoll->url,
                    'text'   => '...',
                    'type'  => 'poll'
                ]);
                $tweet->poll()->save(new \App\Models\Poll([
                    'start_at'  => $alphaPoll->start_at,
                    'end_at'  => $alphaPoll->end_at,
                    'total_voters' => $alphaPoll->total_voter
                ]));
            }
        }
        $this->info('Poll migrated!');

    }

    protected function migratePollResults()
    {

    }
}
