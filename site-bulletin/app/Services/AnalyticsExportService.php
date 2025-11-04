<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Collection;

class AnalyticsExportService
{
    public function __construct(protected SLAService $slaService)
    {
    }

    /**
     * @return array{headers: array<int,string>, rows: \Illuminate\Support\Collection<int,array<int,string>>}
     */
    public function generateTicketExport(): array
    {
        $tickets = Ticket::with(['category', 'assignee', 'requester'])->orderByDesc('created_at')->get();

        $headers = [
            'Ticket ID',
            'Title',
            'Priority',
            'Status',
            'Category',
            'Requester',
            'Assignee',
            'First Response (mins)',
            'Resolution Active (mins)',
            'First Response Breach',
            'Resolution Breach',
        ];

        $rows = $tickets->map(function (Ticket $ticket) {
            $sla = $this->slaService->evaluate($ticket);

            return [
                $ticket->id,
                $ticket->title,
                $ticket->priority->value ?? $ticket->priority,
                $ticket->status->value ?? $ticket->status,
                $ticket->category->name ?? 'Uncategorised',
                $ticket->requester->name,
                $ticket->assignee->name ?? 'Unassigned',
                $sla['first_response_minutes'] ?? 'n/a',
                $sla['resolution_active_minutes'] ?? 'n/a',
                $sla['first_response_breached'] ? 'yes' : 'no',
                $sla['resolution_breached'] ? 'yes' : 'no',
            ];
        });

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function toCsv(array $exportData): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $exportData['headers']);

        /** @var Collection<int, array<int, string>> $rows */
        $rows = $exportData['rows'];
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }
}
