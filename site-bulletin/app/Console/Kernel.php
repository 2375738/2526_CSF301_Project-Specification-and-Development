<?php

namespace App\Console;

use App\Console\Commands\RecalculateDepartmentMetrics;
use App\Console\Commands\RecalculateTicketSLA;
use App\Console\Commands\SendAnalyticsDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        RecalculateTicketSLA::class,
        RecalculateDepartmentMetrics::class,
        SendAnalyticsDigest::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tickets:recalculate-sla')->dailyAt('00:30');
        $schedule->command('analytics:recalculate-departments')->dailyAt('01:00');
        $schedule->command('analytics:send-digest')->weekdays()->at('07:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
