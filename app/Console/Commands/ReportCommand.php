<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Report;

class ReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report {--interval= : The interval of report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate report based on selected time interval';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = $this->option('interval');
        Report::generate(interval: $interval);
    }
}
