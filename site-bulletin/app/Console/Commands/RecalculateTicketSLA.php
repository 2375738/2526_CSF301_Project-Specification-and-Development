<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\SLAService;
use Illuminate\Console\Command;

class RecalculateTicketSLA extends Command
{
    protected $signature = 'tickets:recalculate-sla';

    protected $description = 'Recalculate SLA breach flags for all tickets.';

    public function handle(SLAService $slaService): int
    {
        Ticket::with('statusChanges')->chunk(200, function ($tickets) use ($slaService) {
            foreach ($tickets as $ticket) {
                $sla = $slaService->evaluate($ticket);
                $ticket->forceFill([
                    'sla_first_response_breached' => $sla['first_response_breached'],
                    'sla_resolution_breached' => $sla['resolution_breached'],
                ])->saveQuietly();
            }
        });

        $this->info('SLA flags recalculated.');

        return Command::SUCCESS;
    }
}
