<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

class DepartmentAnalyticsService
{
    public function __construct(protected SLAService $slaService)
    {
    }

    public function recalculateForDate(Carbon $date): void
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $departmentIds = Department::query()->pluck('id')->prepend(null);

        foreach ($departmentIds as $departmentId) {
            $tickets = Ticket::query()
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->get();

            $openTickets = $tickets
                ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
                ->count();

            $breaches = $tickets->filter(function (Ticket $ticket) {
                return ($ticket->sla_first_response_breached ?? false) || ($ticket->sla_resolution_breached ?? false);
            })->count();

            $messages = Message::query()
                ->whereBetween('created_at', [$start, $end])
                ->when($departmentId, function ($query) use ($departmentId) {
                    $query->whereHas('conversation', fn ($conversation) => $conversation->where('department_id', $departmentId));
                })
                ->count();

            $evaluations = $tickets->map(fn (Ticket $ticket) => $this->slaService->evaluate($ticket));

            $avgFirstResponse = (int) round($evaluations
                ->pluck('first_response_minutes')
                ->filter()
                ->avg() ?? 0);

            $avgResolution = (int) round($evaluations
                ->pluck('resolution_active_minutes')
                ->filter()
                ->avg() ?? 0);

            DepartmentMetric::updateOrCreate(
                [
                    'department_id' => $departmentId,
                    'metric_date' => $start->toDateString(),
                ],
                [
                    'open_tickets' => $openTickets,
                    'sla_breaches' => $breaches,
                    'messages_sent' => $messages,
                    'avg_first_response_minutes' => $avgFirstResponse ?: null,
                    'avg_resolution_minutes' => $avgResolution ?: null,
                ]
            );
        }
    }

    public function trend(int $days = 7)
    {
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        return DepartmentMetric::query()
            ->where('metric_date', '>=', $start->toDateString())
            ->orderBy('metric_date')
            ->get()
            ->groupBy('department_id');
    }
}
