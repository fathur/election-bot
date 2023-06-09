<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use App\Enums\ReportInterval;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Models\Tweet;
use App\Models\Account;
use App\Models\Poll;
use App\Models\PollChoice;
use App\Models\Report as ReportModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Twitter\Twitter;
use App\Exceptions\ReportException;
use Illuminate\Support\Facades\Cache;
use App\Services\Twitter\QueryBuilder;
use Illuminate\Support\Str;

class Report
{
    protected $twitter;
    protected $choices;
    protected $me;

    public function __construct()
    {
        $this->choices = PollChoice::all();
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
    }

    public static function generate(string $interval)
    {
        Log::info("{$interval} report generating...");
        if ($interval == ReportInterval::DAILY->text()) {
            (new self())->generateDailyReport();
        }
        Log::info("{$interval} report generated!");

    }

    public function generateDailyReport(string $until = null)
    {
        Log::info("Report generating...");

        if ($until) {
            $untilAt = Carbon::parse($until);
        } else {
            $untilAt = Carbon::now()->subDay(1);
        }

        $polls = Poll::with('tweet')
            ->whereNull('total_voters')
            ->where('end_at', '<=', $untilAt)
            ->orderBy('end_at', 'asc')
            ->get();


        $chunks = $polls->chunk(100);

        foreach ($chunks as $chunkPolls) {
            $ids = $chunkPolls->pluck('tweet.twitter_id')->join(',');

            $this->getPolls($ids);
        }

        if (count($polls) == 0) {
            Log::warning("No data to generate!");
            return;
        }

        $report = $this->storeReport($polls);

        $this->postReportToTwitter($report);

        Log::info("Report generated!");

    }

    protected function storeReport($polls)
    {
        Log::info("Storing to db...");

        $pollIds = $polls->pluck('id');

        $pollQueryBuilder = Poll::whereIn('id', $pollIds)->where('total_voters', '>', 0);
        $reportTotalVoters = $pollQueryBuilder->sum('total_voters');
        $reportStartAt = $pollQueryBuilder->min('poll_start_at');
        $reportEndAt = $pollQueryBuilder->max('poll_end_at');

        $resume = [];
        $syncData = [];
        foreach ($this->choices as $choice) {
            $choiceTotalVoters = $choice->polls()->whereIn('poll_results.poll_id', $pollIds)
                ->sum('poll_results.total_voters');
            $item = [
                'option' => $choice->option,
                'voters' => $choiceTotalVoters
            ];
            array_push($resume, $item);
            $syncData[$choice->id] = ['total_voters' => $choiceTotalVoters];
        }
        $resume = collect($resume)->sortByDesc('voters')->all();
        $resume = array_values($resume);


        $report = ReportModel::create([
            'interval'  => 'daily',
            'start_at' => $reportStartAt,
            'end_at' => $reportEndAt,
            'total_voters' => $reportTotalVoters,
            'total_polls' => count($polls),
            'resume' => json_encode($resume)
        ]);
        $report->choices()->sync($syncData);
        $report->polls()->sync($pollIds->all());

        Log::info("Stored to db!");
        return $report;

    }

    protected function getPolls(string|array $ids)
    {
        Log::info("Poll fetching...");
        Log::info([
            "ids" => $ids
        ]);

        $tweetFields = ['created_at', 'public_metrics'];
        if (App::environment('production')) {
            array_push($tweetFields, 'organic_metrics');
        }

        // Request to twitter with 100 ids
        $result = $this->twitter->getTweets(
            ids: $ids,
            expansions: 'attachments.poll_ids,author_id',
            pollFields: 'duration_minutes,end_datetime,id,options,voting_status',
            tweetFields: collect($tweetFields)->join(',')
        );

        if (property_exists($result, 'errors') and ($result->errors != null || count($result->errors) > 0)) {
            throw new ReportException("Failed to get the twitter data");
        }

        $twitterTweets = collect($result->data);
        $includes = $result->includes;
        $twitterPolls = collect($includes->polls);

        $tweets = Tweet::with('poll')
            ->whereIn('twitter_id', $twitterTweets->pluck('id')->all())
            ->get();

        foreach ($tweets as $tweet) {

            Log::info("`status/{$tweet->twitter_id}` is being processing...");

            $twitterTweet = $twitterTweets->where('id', $tweet->twitter_id)->first();

            $tweet->text = $twitterTweet->text;

            Log::info('Metric tweet updating...');
            $publicMetrics = $twitterTweet->public_metrics;
            $tweet->total_retweets = $publicMetrics->retweet_count;
            $tweet->total_comments = $publicMetrics->reply_count;
            $tweet->total_likes = $publicMetrics->like_count;
            $tweet->total_quotes = $publicMetrics->quote_count;

            if (App::environment('production')) {
                $organicMetrics = $twitterTweet->organic_metrics;

                $tweet->total_impressions = $organicMetrics->impression_count;
                $tweet->total_profile_clicks = $organicMetrics->user_profile_clicks;
            } else {
                $tweet->total_impressions = $publicMetrics->impression_count;
            }

            $tweet->metric_fetch_at = Carbon::now();
            $tweet->save();
            Log::info('Metric tweet updated!');

            $twitterPoll = $twitterPolls->where('id', $twitterTweet->attachments->poll_ids[0])->first();
            if ($twitterPoll->voting_status !== 'closed') {
                Log::warning("Poll not finished yet!");
                continue;
            }

            $poll = $tweet->poll;
            $poll->twitter_id = $twitterPoll->id;
            $poll->poll_start_at = Carbon::parse($twitterTweet->created_at);
            $poll->poll_end_at = Carbon::parse($twitterPoll->end_datetime);

            $syncedData = [];
            $totalPollVoters = 0;
            foreach ($twitterPoll->options as $option) {

                // Typo make error
                if ($option->label == "Ganjar Pranomo") {
                    $label = "Ganjar Pranowo";
                } else {
                    $label = $option->label;
                }

                $choice = $this->choices->where('option', $label)->first();
                $candidateId = $choice->id;
                $totalOptionVoters = $option->votes;

                $syncedData[$choice->id] = [
                    'total_voters'  => $totalOptionVoters
                ];

                $totalPollVoters += $totalOptionVoters;
            }

            $poll->choices()->sync($syncedData);

            $poll->total_voters = $totalPollVoters;
            $poll->save();

            Log::info("`status/{$tweet->twitter_id}` is done processed!");

        }

        Log::info("Poll fetched!");

    }

    protected function postReportToTwitter($report)
    {
        $humanStartAt = $report->start_at->addHours(7)->format('d M Y H:i');
        $humanEndAt = $report->end_at->addHours(7)->format('d M Y H:i');

        $tweet = <<<TXT
Berikut hasil #poll dari {$report->total_voters} voter di {$report->total_polls} #polling 
yang dimulai dari {$humanStartAt} hingga {$humanEndAt} WIB:\n\n

TXT;
        $candidateResult = "";
        $i = 1;
        foreach (json_decode($report->resume) as $item) {
            $percentage = ($item->voters / $report->total_voters) * 100;
            $percentage = round($percentage, 2);
            $candidate = Str::of($item->option)->prepend('#')->title()->remove(' ');
            $candidateResult .= "{$i}. {$candidate}: {$item->voters} ({$percentage}%)\n";
            $i++;
        }

        $tweet .= $candidateResult;
        $tweet .= "\n 🤖";
        Log::info([
            "message" => $tweet
        ]);

        $response = $this->twitter->createTweet($tweet);
        $twitterTweet = $response->data;

        $account = Account::where('username', $this->me->username)->first();

        Log::info([
            'account' => $account->id
        ]);

        $tweet = $account->tweets()->create([
            'twitter_id' => $twitterTweet->id,
            'url'   => "https://twitter.com/{$account->username}/status/{$twitterTweet->id}",
            'text'  => $twitterTweet->text,
            'type'  => 'report'
        ]);

        Log::info([
            'tweet' => $tweet->id
        ]);
    }
}
