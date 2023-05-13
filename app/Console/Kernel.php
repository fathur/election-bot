<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Run candidate schedule every days every 15 minutes
        $schedule->command('poll --for=candidate')->everyFifteenMinutes();

        // Run media schedule every 15 minutes in Monday until Friday, at 6 am until 9 am, 12pm until 1pm, and 5pm until 9pm
        $schedule->command('poll --for=media')->everyFifteenMinutes()
            ->between('6:00', '9:00')
            ->between('12:00', '13:00')
            ->between('17:00', '21:00')
            ->days([1, 2, 3, 4, 5])
            ->withoutOverlapping();

        // Run media schedule every 15 minutes in Saturday until Sunday, at 6 am untul 10pm
        $schedule->command('poll --for=media')->everyFifteenMinutes()
            ->between('17:00', '21:00')
            ->days([0, 6])
            ->withoutOverlapping();

        // Generate report daily
        $schedule->command('report --interval=daily')
            ->dailyAt('6:00');

        // Generate report weekly on Monday at 6:00
        $schedule->command('report --interval=weekly')
            ->weeklyOn(1, '6:00');

        // Generate report monthly on the first day of every month at 00:00
        $schedule->command('report --interval=monthly')
            ->monthly();

        // Generate report quarterly on the first day of every quarter at 00:00
        $schedule->command('report --interval=quarterly')
            ->quarterly();

        // Generate report yearly on the first day of every year at 00:00
        $schedule->command('report --interval=yearly')
            ->yearly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
