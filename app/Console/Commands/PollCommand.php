<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Poll;
use Illuminate\Support\Facades\Log;

class PollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll {--target= : Where the poll should be placed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a polling in Twitter.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info("Command poll --target running...");

        $target = $this->option('target');
        Poll::run(target: $target);

        Log::info("Command poll --target={$target} run successfully!");
    }
}
