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
            $scopedTickets = Ticket::query()
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('created_at', [$start, $end])
                        ->orWhereBetween('updated_at', [$start, $end])
                        ->orWhereBetween('closed_at', [$start, $end]);
                })
                ->get();

            $openTickets = Ticket::query()
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->where('created_at', '<=', $end)
                ->where(function ($query) use ($end) {
                    $query->whereNull('closed_at')
                        ->orWhere('closed_at', '>', $end);
                })
                ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
                ->count();

            $breaches = $scopedTickets->filter(function (Ticket $ticket) {
                return ($ticket->sla_first_response_breached ?? false) || ($ticket->sla_resolution_breached ?? false);
            })->count();

            $messages = Message::query()
                ->whereBetween('created_at', [$start, $end])
                ->when($departmentId, function ($query) use ($departmentId) {
                    $query->whereHas('conversation', fn ($conversation) => $conversation->where('department_id', $departmentId));
                })
                ->count();

            $evaluations = $scopedTickets->map(fn (Ticket $ticket) => $this->slaService->evaluate($ticket));

            $avgFirstResponse = (int) round($evaluations
                ->pluck('first_response_minutes')
                ->filter()
                ->avg() ?? 0);

            $avgResolution = (int) round($evaluations
                ->pluck('resolution_active_minutes')
                ->filter()
                ->avg() ?? 0);

            DepartmentMetric::query()->upsert(
                [[
                    'department_id' => $departmentId,
                    'metric_date' => $start->copy()->startOfDay()->toDateTimeString(),
                    'open_tickets' => $openTickets,
                    'sla_breaches' => $breaches,
                    'messages_sent' => $messages,
                    'avg_first_response_minutes' => $avgFirstResponse ?: null,
                    'avg_resolution_minutes' => $avgResolution ?: null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]],
                ['department_id', 'metric_date'],
                ['open_tickets', 'sla_breaches', 'messages_sent', 'avg_first_response_minutes', 'avg_resolution_minutes', 'updated_at']
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

    public function ensureRecentWindow(?int $departmentId, int $days = 7): void
    {
        if (! $departmentId) {
            return;
        }

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = Carbon::now()->subDays($offset);

            $exists = DepartmentMetric::query()
                ->where('department_id', $departmentId)
                ->whereDate('metric_date', $date->toDateString())
                ->exists();

            if (! $exists) {
                $this->recalculateForDate($date);
            }
        }
    }
}
