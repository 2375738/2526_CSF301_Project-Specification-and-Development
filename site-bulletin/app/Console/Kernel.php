<?php

namespace App\Console;

use App\Console\Commands\BackfillDemoOperationsData;
use App\Console\Commands\RecalculateDepartmentMetrics;
use App\Console\Commands\RollupPerformanceSamples;
use App\Console\Commands\RecalculateTicketSLA;
use App\Console\Commands\SendAnalyticsDigest;
use App\Console\Commands\SystemReadinessCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        BackfillDemoOperationsData::class,
        RecalculateTicketSLA::class,
        RecalculateDepartmentMetrics::class,
        RollupPerformanceSamples::class,
        SendAnalyticsDigest::class,
        SystemReadinessCheck::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        if (config('site_bulletin.demo_simulation_enabled')) {
            $schedule->command('demo:backfill-ops-data')->everyFifteenMinutes();
        }

        $schedule->command('performance:rollup-samples --days=8')->hourlyAt(10);
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
