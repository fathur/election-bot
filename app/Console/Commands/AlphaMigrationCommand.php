<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\Twitter\QueryBuilder;
use App\Exceptions\PollBotException;

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
        $this->migrateChoices();
        $this->migrateAccounts();
        $this->migrateTweets();
        $this->migratePolls();
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
                $choice = new \App\Models\PollChoice();
                $choice->option =  $alphaChoice->option;
                $choice->alpha_poll_choice_id = $alphaChoice->id;


            }

            $choice->timestamps = false;
            $choice->created_at = $alphaChoice->created_at;
            $choice->updated_at = $alphaChoice->updated_at;
            $choice->save();

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
            } else {
                $account = new \App\Models\Account();
                $account->timestamps = false;
                $account->twitter_id = $alphaAccount->object_id;
                $account->username = $alphaAccount->username;
                $account->name = $alphaAccount->name;
                $account->alpha_account_id = $alphaAccount->id;
            }

            $account->timestamps = false;

            $account->created_at = $alphaAccount->created_at;
            $account->updated_at = $alphaAccount->updated_at;
            $account->save();

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
            } else {
                $tweet = new \App\Models\Tweet();
                $tweet->twitter_id = $alphaTweet->object_id;
                $tweet->alpha_tweet_id = $alphaTweet->id;
                $tweet->account_id    = \App\Models\Account::where('alpha_account_id', $alphaTweet->account_id)->first()->id;
                $tweet->url   = $alphaTweet->url;
                $tweet->text   = $alphaTweet->text;
                $tweet->type  = 'text';
            }

            $tweet->timestamps = false;

            $tweet->created_at = $alphaTweet->created_at;
            $tweet->updated_at = $alphaTweet->updated_at;
            $tweet->save();

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

                $tweet->timestamps = false;
                $tweet->created_at = $alphaPoll->created_at;
                $tweet->updated_at = $alphaPoll->updated_at;
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

                $tweet = new \App\Models\Tweet();
                $tweet->twitter_id = $alphaPoll->object_id;
                $tweet->parent_id = \App\Models\Tweet::where('alpha_tweet_id', $alphaPoll->tweet_id)->first()->id;
                $tweet->alpha_poll_tweet_id = $alphaPoll->id;
                $tweet->account_id    = \App\Models\Account::where('username', $me->username)->first()->id;
                $tweet->url   = $alphaPoll->url;
                $tweet->text   = '...';
                $tweet->type  = 'poll';

                $tweet->timestamps = false;
                $tweet->created_at = $alphaPoll->created_at;
                $tweet->updated_at = $alphaPoll->updated_at;
                $tweet->save();

                $poll = new \App\Models\Poll();
                $poll->start_at  = $alphaPoll->start_at;
                $poll->end_at  = $alphaPoll->end_at;
                $poll->total_voters = $alphaPoll->total_voter;

                $poll->timestamps = false;
                $poll->created_at = $alphaPoll->created_at;
                $poll->updated_at = $alphaPoll->updated_at;

                $tweet->poll()->save($poll);
            }
        }
        $this->info('Poll migrated!');

    }

    protected function migratePollResults()
    {
        foreach (\App\Models\Alpha\Poll::lazy() as $alphaPoll) {

            $tweet = \App\Models\Tweet::where('type', 'poll')->where('alpha_poll_tweet_id', $alphaPoll->id)->first();
            if (!$tweet) {
                continue;
            }

            $attacheData = [];

            foreach ($alphaPoll->choices as $alphaChoice) {
                $choice = \App\Models\PollChoice::where('alpha_poll_choice_id', $alphaChoice->id)->first();
                $attacheData[$choice->id] = [
                    'total_voters' => $alphaChoice->pivot->total_voter,
                    'alpha_poll_result_id' => $alphaChoice->pivot->id,
                    'created_at' => $alphaChoice->pivot->created_at,
                    'updated_at' => $alphaChoice->pivot->updated_at,
                ];

            }

            $tweet->poll->choices()->sync($attacheData);
        }
    }
}
