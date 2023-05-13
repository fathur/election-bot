<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Poll;

class PollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poll {--for= : Where the poll should be placed}';

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
        $for = $this->option('for');
        Poll::run(for: $for);
    }
}
