<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SLAService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function index(Request $request, SLAService $slaService)
    {
        $openByPriority = Ticket::query()
            ->select('priority', DB::raw('count(*) as total'))
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority, 'critical','high','medium','low')")
            ->pluck('total', 'priority');

        $since = Carbon::now()->subDays(30);
        $recentTickets = Ticket::with(['category', 'assignee'])
            ->where('created_at', '>=', $since)
            ->get();

        $evaluations = $recentTickets->map(fn (Ticket $ticket) => $slaService->evaluate($ticket) + ['ticket' => $ticket]);

        $firstResponseMinutes = $evaluations
            ->pluck('first_response_minutes')
            ->filter()
            ->avg();

        $resolutionMinutes = $evaluations
            ->pluck('resolution_active_minutes')
            ->filter()
            ->avg();

        $breachesLastWeek = Ticket::with('category')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->get()
            ->filter(fn (Ticket $ticket) => ($evaluation = $slaService->evaluate($ticket)) && ($evaluation['first_response_breached'] || $evaluation['resolution_breached']))
            ->count();

        $topCategories = Ticket::query()
            ->leftJoin('categories', 'tickets.category_id', '=', 'categories.id')
            ->select(DB::raw('COALESCE(categories.name, "Uncategorised") as category_name'), DB::raw('count(*) as total'))
            ->groupBy('category_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentActivity = $recentTickets
            ->sortByDesc(fn (Ticket $ticket) => $ticket->updated_at)
            ->take(8);

        return view('admin.analytics', [
            'openByPriority' => $openByPriority,
            'breachesLastWeek' => $breachesLastWeek,
            'firstResponseAvg' => $firstResponseMinutes,
            'resolutionAvg' => $resolutionMinutes,
            'topCategories' => $topCategories,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function export(SLAService $slaService): StreamedResponse
    {
        $tickets = Ticket::with(['category', 'assignee', 'requester'])->orderByDesc('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="site-analytics.csv"',
        ];

        $callback = function () use ($tickets, $slaService) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
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
            ]);

            foreach ($tickets as $ticket) {
                $sla = $slaService->evaluate($ticket);

                fputcsv($handle, [
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
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, 'site-analytics.csv', $headers);
    }
}
