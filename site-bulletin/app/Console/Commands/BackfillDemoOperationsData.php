<?php

namespace App\Console\Commands;

use App\Services\DemoOperationsSimulationService;
use App\Services\DemoTicketLifecycleSimulationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillDemoOperationsData extends Command
{
    protected $signature = 'demo:backfill-ops-data
        {--start= : Optional aligned start timestamp}
        {--end= : Optional aligned end timestamp}
        {--weeks=6 : Historical weeks to seed when refreshing}
        {--ticket-days=7 : Rolling SLA simulation window in days}
        {--refresh : Rebuild the full historical sample window before backfilling}';

    protected $description = 'Generate or backfill deterministic 15-minute demo operations samples up to a target time.';

    public function handle(
        DemoOperationsSimulationService $simulation,
        DemoTicketLifecycleSimulationService $ticketSimulation
    ): int
    {
        $end = $this->option('end')
            ? Carbon::parse((string) $this->option('end'))
            : Carbon::now();

        if ($this->option('refresh')) {
            $weeks = max(1, (int) $this->option('weeks'));
            $result = $simulation->seedHistoricalWindow($weeks, $end);
        } elseif ($this->option('start')) {
            $start = Carbon::parse((string) $this->option('start'));
            $result = $simulation->backfillRange($start, $end);
        } else {
            $result = $simulation->ensureFreshSamples($end, max(1, (int) $this->option('weeks')));
        }

        $ticketResult = $ticketSimulation->refreshWindow(max(1, (int) $this->option('ticket-days')), $end);

        $this->info(sprintf(
            'Performance data %s from %s to %s for %d users across %d intervals.',
            $result['mode'],
            $result['start'],
            $result['end'],
            $result['users'],
            $result['generated_intervals']
        ));
        $this->info(sprintf(
            'SLA simulation refreshed from %s to %s with %d generated tickets.',
            $ticketResult['start'],
            $ticketResult['end'],
            $ticketResult['tickets']
        ));

        return Command::SUCCESS;
    }
}
