<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalPreventionService
{
    public function __construct(
        protected RoleScopeService $roleScope
    ) {
    }

    public function forUser(User $user, int $days = 14, ?int $departmentId = null): array
    {
        $days = max(1, $days);
        $openStatuses = array_map(fn ($status) => $status->value, TicketStatus::open());

        $baseQuery = Ticket::query()
            ->with(['category:id,name', 'department:id,name'])
            ->when($departmentId, fn (Builder $query) => $query->where('department_id', $departmentId));

        if (! $departmentId) {
            $this->roleScope->applyManageableDepartmentScope($baseQuery, $user);
        }

        $recentTickets = (clone $baseQuery)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->where('status', '!=', TicketStatus::Cancelled->value)
            ->latest('created_at')
            ->get();

        $openTickets = (clone $baseQuery)
            ->whereIn('status', $openStatuses)
            ->get();

        $breachedTickets = $openTickets->filter($this->isBreached(...))->values();
        $agingTickets = $openTickets->filter(fn (Ticket $ticket) => $ticket->created_at?->lessThanOrEqualTo(now()->subHours(48)))->values();
        $unassignedTickets = $openTickets->whereNull('assignee_id')->values();

        return [
            'scope_label' => $this->scopeLabel($user, $departmentId),
            'window_days' => $days,
            'repeat_clusters' => $this->repeatClusters($recentTickets),
            'pressure' => [
                'open_count' => $openTickets->count(),
                'breached_count' => $breachedTickets->count(),
                'aging_count' => $agingTickets->count(),
                'unassigned_count' => $unassignedTickets->count(),
                'breach_rate' => $openTickets->isEmpty() ? 0.0 : round(($breachedTickets->count() / $openTickets->count()) * 100, 1),
                'oldest_age_hours' => $this->oldestAgeHours($openTickets),
            ],
            'breach_risks' => $this->breachRisks($breachedTickets->merge($agingTickets)->unique('id')->values()),
        ];
    }

    protected function repeatClusters(Collection $tickets): Collection
    {
        return $tickets
            ->groupBy(fn (Ticket $ticket) => implode('|', [
                $ticket->department_id ?: 'none',
                $ticket->category_id ?: 'none',
                $ticket->template_key ?: 'none',
                strtolower(trim((string) $ticket->location)) ?: 'none',
            ]))
            ->map(function (Collection $cluster) {
                /** @var Ticket $latest */
                $latest = $cluster->sortByDesc('created_at')->first();
                $open = $cluster->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))->count();
                $breached = $cluster->filter($this->isBreached(...))->count();

                return [
                    'department_id' => $latest->department_id,
                    'department_name' => $latest->department?->name ?? 'No department',
                    'category_id' => $latest->category_id,
                    'category_name' => $latest->category?->name ?? 'Uncategorised',
                    'template_key' => $latest->template_key,
                    'template_label' => $this->templateLabel($latest->template_key),
                    'location' => $latest->location ?: 'No location',
                    'total' => $cluster->count(),
                    'open_count' => $open,
                    'breached_count' => $breached,
                    'latest_ticket_id' => $latest->id,
                    'latest_title' => $latest->title,
                    'latest_human' => $latest->created_at?->diffForHumans() ?? 'recently',
                    'query' => array_filter([
                        'department_id' => $latest->department_id,
                        'category_id' => $latest->category_id,
                        'template_key' => $latest->template_key,
                        'location' => $latest->location,
                    ], fn ($value) => $value !== null && $value !== ''),
                ];
            })
            ->filter(fn (array $cluster) => $cluster['total'] >= 2)
            ->sortByDesc(fn (array $cluster) => [$cluster['breached_count'], $cluster['open_count'], $cluster['total']])
            ->take(5)
            ->values();
    }

    protected function breachRisks(Collection $tickets): Collection
    {
        return $tickets
            ->sortByDesc(fn (Ticket $ticket) => [
                $this->isBreached($ticket) ? 1 : 0,
                $ticket->created_at?->timestamp ?? 0,
            ])
            ->take(5)
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'department_name' => $ticket->department?->name ?? 'No department',
                'category_name' => $ticket->category?->name ?? 'Uncategorised',
                'age_hours' => $ticket->created_at ? max(0, (int) round($ticket->created_at->diffInMinutes(now()) / 60)) : 0,
                'is_breached' => $this->isBreached($ticket),
            ])
            ->values();
    }

    protected function oldestAgeHours(Collection $tickets): int
    {
        $oldest = $tickets->sortBy('created_at')->first();

        if (! $oldest?->created_at) {
            return 0;
        }

        return max(0, (int) round($oldest->created_at->diffInMinutes(now()) / 60));
    }

    protected function isBreached(Ticket $ticket): bool
    {
        return (bool) ($ticket->sla_first_response_breached ?? false)
            || (bool) ($ticket->sla_resolution_breached ?? false);
    }

    protected function templateLabel(?string $templateKey): string
    {
        if (! $templateKey) {
            return 'No template';
        }

        return (string) (config("ticket_templates.{$templateKey}.label") ?? str($templateKey)->replace('_', ' ')->title());
    }

    protected function scopeLabel(User $user, ?int $departmentId): string
    {
        if ($departmentId) {
            return 'Selected department';
        }

        return $this->roleScope->manageableDepartmentIds($user) === null
            ? 'Site-wide'
            : 'Managed departments';
    }
}
