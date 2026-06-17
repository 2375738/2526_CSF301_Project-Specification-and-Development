<?php

namespace App\Console\Commands;

use App\Services\PerformanceRollupService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RollupPerformanceSamples extends Command
{
    protected $signature = 'performance:rollup-samples
        {--start= : Optional rollup start timestamp}
        {--end= : Optional rollup end timestamp}
        {--days=7 : Rolling window to aggregate when start is omitted}
        {--prune-raw-after-days= : Delete raw 15-minute samples older than this many days after rollup}';

    protected $description = 'Aggregate 15-minute performance samples into hourly and daily chart rollups.';

    public function handle(PerformanceRollupService $rollups): int
    {
        $end = $this->option('end')
            ? Carbon::parse((string) $this->option('end'))
            : now();
        $start = $this->option('start')
            ? Carbon::parse((string) $this->option('start'))
            : $end->copy()->subDays(max(1, (int) $this->option('days')));

        $result = $rollups->refreshRange($start, $end);

        $this->info(sprintf(
            'Rolled up %d raw samples into %d hourly and %d daily buckets.',
            $result['samples'],
            $result['hourly'],
            $result['daily']
        ));

        if ($this->option('prune-raw-after-days') !== null) {
            $days = max(1, (int) $this->option('prune-raw-after-days'));
            $deleted = $rollups->pruneRawSamplesOlderThan($days);
            $this->info(sprintf('Pruned %d raw samples older than %d days.', $deleted, $days));
        }

        return Command::SUCCESS;
    }
}
