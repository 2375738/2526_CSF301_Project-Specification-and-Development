<?php

namespace App\Console\Commands;

use App\Services\DepartmentAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecalculateDepartmentMetrics extends Command
{
    protected $signature = 'analytics:recalculate-departments {--date=}';

    protected $description = 'Recalculate department analytics metrics for a given date (default: today).';

    public function handle(DepartmentAnalyticsService $analytics): int
    {
        $dateInput = $this->option('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::now();

        $analytics->recalculateForDate($date);

        $this->info(sprintf('Department metrics generated for %s.', $date->toDateString()));

        return Command::SUCCESS;
    }
}
