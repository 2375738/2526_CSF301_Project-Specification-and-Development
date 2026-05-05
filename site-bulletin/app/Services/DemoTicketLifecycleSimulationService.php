<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoTicketLifecycleSimulationService
{
    public function __construct(
        protected SLAService $slaService,
        protected DepartmentAnalyticsService $departmentAnalytics
    ) {
    }

    public function refreshWindow(int $days = 7, ?Carbon $reference = null): array
    {
        $end = $this->alignToQuarterHour($reference ?? now());
        $start = $end->copy()->subDays(max(1, $days) - 1)->startOfDay();

        Ticket::query()
            ->whereNotNull('simulation_key')
            ->where('simulation_key', 'like', 'demo-sla:%')
            ->delete();

        $departments = Department::query()->with(['members', 'managers'])->get();
        $ticketCategories = Category::query()
            ->whereDoesntHave('links')
            ->get();

        $createdCount = 0;

        foreach ($departments as $department) {
            $departmentMembers = User::query()
                ->where(function ($query) use ($department) {
                    $query->where('primary_department_id', $department->id)
                        ->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery
                            ->where('departments.id', $department->id));
                })
                ->get();
            $employees = $departmentMembers->filter(fn (User $user) => $user->isEmployee())->values();
            $managers = $departmentMembers->filter(fn (User $user) => $user->hasRole('manager', 'ops_manager', 'hr', 'admin'))->values();

            if ($departmentMembers->isEmpty() || $ticketCategories->isEmpty()) {
                continue;
            }

            for ($bucket = $start->copy(); $bucket->lessThanOrEqualTo($end); $bucket->addHours(3)) {
                $perBucket = $this->bucketTicketCount($department->slug, $bucket);

                foreach (range(0, $perBucket - 1) as $slot) {
                    $simulationKey = sprintf(
                        'demo-sla:%d:%s:%d',
                        $department->id,
                        $bucket->format('YmdHi'),
                        $slot
                    );

                    $ticket = $this->buildTicketForBucket(
                        $simulationKey,
                        $department,
                        $employees,
                        $departmentMembers,
                        $managers,
                        $ticketCategories,
                        $bucket->copy(),
                        $end,
                        $slot
                    );

                    if ($ticket) {
                        $createdCount++;
                    }
                }
            }
        }

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $this->departmentAnalytics->recalculateForDate($cursor->copy());
        }

        return [
            'mode' => 'refresh',
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'tickets' => $createdCount,
        ];
    }

    protected function buildTicketForBucket(
        string $simulationKey,
        Department $department,
        Collection $employees,
        Collection $departmentMembers,
        Collection $managers,
        Collection $ticketCategories,
        Carbon $bucket,
        Carbon $reference,
        int $slot
    ): ?Ticket {
        $requester = $this->pickUser($employees->isNotEmpty() ? $employees : $departmentMembers, $simulationKey . ':requester');
        $affectedUser = $this->pickUser($employees->isNotEmpty() ? $employees : $departmentMembers, $simulationKey . ':affected');
        $assignee = $this->pickUser($managers->isNotEmpty() ? $managers : $departmentMembers, $simulationKey . ':assignee');
        $category = $this->pickCategory($ticketCategories, $simulationKey . ':category');

        if (! $requester || ! $assignee || ! $category) {
            return null;
        }

        $priority = $this->pickPriority($simulationKey);
        $targets = $this->slaService->targets($priority);
        $createdAt = $bucket->copy()->addMinutes($this->seededInt($simulationKey . ':created-offset', 0, 165));
        $latestAllowed = $reference->copy()->subMinutes(5);

        if ($createdAt->greaterThan($latestAllowed)) {
            $createdAt = $latestAllowed->copy();
        }

        $firstResponseBreached = $this->seededRatio($simulationKey . ':first-response-breached') > 0.92;
        $firstResponseMinutes = $firstResponseBreached
            ? $this->seededInt($simulationKey . ':first-response', $targets['first_response_minutes'] + 10, max($targets['first_response_minutes'] + 30, $targets['first_response_minutes'] * 2))
            : $this->seededInt($simulationKey . ':first-response', 6, max(8, (int) floor($targets['first_response_minutes'] * 0.85)));

        $triagedAt = $createdAt->copy()->addMinutes($firstResponseMinutes);
        if ($triagedAt->greaterThan($latestAllowed)) {
            $triagedAt = $latestAllowed->copy();
        }

        $finalStatus = $this->determineFinalStatus($simulationKey);
        $isOpenStatus = in_array($finalStatus, [TicketStatus::Triaged->value, TicketStatus::InProgress->value, TicketStatus::WaitingEmployee->value, TicketStatus::Reopened->value], true);
        $openAgeMinutes = null;

        if ($isOpenStatus) {
            $ageRatio = $this->seededRatio($simulationKey . ':open-age-band');
            [$minAge, $maxAge] = match (true) {
                $ageRatio < 0.65 => [
                    max($firstResponseMinutes + 20, 30),
                    max($firstResponseMinutes + 30, (int) floor($targets['resolution_minutes'] * 0.55)),
                ],
                $ageRatio < 0.9 => [
                    max($firstResponseMinutes + 45, (int) floor($targets['resolution_minutes'] * 0.56)),
                    max($firstResponseMinutes + 60, (int) floor($targets['resolution_minutes'] * 0.92)),
                ],
                default => [
                    max($firstResponseMinutes + 60, (int) floor($targets['resolution_minutes'] * 1.05)),
                    max($firstResponseMinutes + 90, (int) floor($targets['resolution_minutes'] * 1.30)),
                ],
            };
            $openAgeMinutes = $this->seededInt($simulationKey . ':open-age', $minAge, $maxAge);

            // Open work should look current. Historical buckets can still seed volume,
            // but unresolved tickets must be aged relative to now for SLA realism.
            $createdAt = $latestAllowed->copy()->subMinutes($openAgeMinutes);
            $triagedAt = $createdAt->copy()->addMinutes($firstResponseMinutes);
            if ($triagedAt->greaterThan($latestAllowed)) {
                $triagedAt = $createdAt->copy()->addMinutes(min(15, max(1, $openAgeMinutes - 1)));
            }
        }

        $timeline = [
            ['from' => null, 'to' => TicketStatus::New->value, 'at' => $createdAt->copy()->addMinute(), 'reason' => 'Ticket opened by requester'],
            ['from' => TicketStatus::New->value, 'to' => TicketStatus::Triaged->value, 'at' => $triagedAt->copy(), 'reason' => 'Acknowledged by manager'],
        ];

        $updatedAt = $triagedAt->copy();
        $closedAt = null;

        if ($isOpenStatus) {
            $updatedAt = $createdAt->copy()->addMinutes($openAgeMinutes);
            if ($updatedAt->greaterThan($latestAllowed)) {
                $updatedAt = $latestAllowed->copy();
            }

            if (in_array($finalStatus, [TicketStatus::InProgress->value, TicketStatus::WaitingEmployee->value, TicketStatus::Reopened->value], true)) {
                $timeline[] = [
                    'from' => TicketStatus::Triaged->value,
                    'to' => TicketStatus::InProgress->value,
                    'at' => $this->midpointTime($triagedAt, $updatedAt, 0.45),
                    'reason' => 'Work started on issue',
                ];
            }

            if (in_array($finalStatus, [TicketStatus::WaitingEmployee->value, TicketStatus::Reopened->value], true)) {
                $timeline[] = [
                    'from' => TicketStatus::InProgress->value,
                    'to' => TicketStatus::WaitingEmployee->value,
                    'at' => $this->midpointTime($triagedAt, $updatedAt, 0.7),
                    'reason' => 'Need more information from requester',
                ];
            }

            if ($finalStatus === TicketStatus::Reopened->value) {
                $timeline[] = [
                    'from' => TicketStatus::WaitingEmployee->value,
                    'to' => TicketStatus::Reopened->value,
                    'at' => $updatedAt->copy(),
                    'reason' => 'Requester provided another update',
                ];
            }
        } else {
            $resolutionBreached = $this->seededRatio($simulationKey . ':resolution-breached') > 0.9;
            $resolutionMinutes = $resolutionBreached
                ? $this->seededInt($simulationKey . ':resolution', $targets['resolution_minutes'] + 30, max($targets['resolution_minutes'] + 120, $targets['resolution_minutes'] * 2))
                : $this->seededInt($simulationKey . ':resolution', max(45, (int) floor($targets['resolution_minutes'] * 0.45)), max(60, (int) floor($targets['resolution_minutes'] * 0.92)));

            $updatedAt = $createdAt->copy()->addMinutes($resolutionMinutes);
            if ($updatedAt->greaterThan($latestAllowed)) {
                $updatedAt = $latestAllowed->copy();
            }

            $resolvedAt = $this->midpointTime($triagedAt, $updatedAt, 0.7);
            $timeline[] = [
                'from' => TicketStatus::Triaged->value,
                'to' => TicketStatus::InProgress->value,
                'at' => $this->midpointTime($triagedAt, $updatedAt, 0.35),
                'reason' => 'Work started on issue',
            ];
            $timeline[] = [
                'from' => TicketStatus::InProgress->value,
                'to' => TicketStatus::Resolved->value,
                'at' => $resolvedAt->copy(),
                'reason' => 'Issue resolved by operations',
            ];

            if ($finalStatus === TicketStatus::Closed->value) {
                $timeline[] = [
                    'from' => TicketStatus::Resolved->value,
                    'to' => TicketStatus::Closed->value,
                    'at' => $updatedAt->copy(),
                    'reason' => 'Ticket closed',
                ];
                $closedAt = $updatedAt->copy();
            } else {
                $closedAt = $resolvedAt->copy();
            }
        }

        $ticket = Ticket::updateOrCreate(
            ['simulation_key' => $simulationKey],
            [
                'requester_id' => $requester->id,
                'assignee_id' => $assignee->id,
                'created_for_id' => $affectedUser?->id,
                'department_id' => $department->id,
                'category_id' => $category->id,
                'template_key' => 'demo_' . Str::slug($category->name, '_'),
                'priority' => $priority,
                'status' => $finalStatus,
                'title' => $this->ticketTitle($department, $category, $bucket, $slot),
                'description' => $this->ticketDescription($department, $category, $createdAt, $slot),
                'location' => $this->ticketLocation($department, $simulationKey),
                'closed_at' => in_array($finalStatus, [TicketStatus::Resolved->value, TicketStatus::Closed->value], true) ? $closedAt : null,
                'details_json' => [
                    'demo_simulated' => true,
                    'scenario' => $category->name,
                    'seed' => $simulationKey,
                ],
            ]
        );

        $ticket->forceFill([
            'created_at' => $createdAt->copy(),
            'updated_at' => $updatedAt->copy(),
        ])->saveQuietly();

        $ticket->statusChanges()->delete();
        $ticket->comments()->delete();

        foreach ($timeline as $step) {
            $change = TicketStatusChange::create([
                'ticket_id' => $ticket->id,
                'user_id' => $step['from'] === null ? $requester->id : $assignee->id,
                'from_status' => $step['from'],
                'to_status' => $step['to'],
                'reason' => $step['reason'],
            ]);

            $change->forceFill([
                'created_at' => $step['at']->copy(),
                'updated_at' => $step['at']->copy(),
            ])->saveQuietly();
        }

        $managerCommentAt = $triagedAt->copy()->addMinutes(min(25, max(5, $updatedAt->diffInMinutes($triagedAt) > 30 ? 15 : 5)));
        if ($managerCommentAt->greaterThan($updatedAt)) {
            $managerCommentAt = $updatedAt->copy()->subMinutes(1);
        }
        $this->createComment($ticket, $assignee, $managerCommentAt, 'Acknowledged. Working on it.');

        if ($updatedAt->greaterThan($managerCommentAt->copy()->addMinutes(20))) {
            $requesterCommentAt = $managerCommentAt->copy()->addMinutes(min(90, max(20, (int) floor($managerCommentAt->diffInMinutes($updatedAt, false) * -0.6))));
            if ($requesterCommentAt->greaterThan($updatedAt)) {
                $requesterCommentAt = $updatedAt->copy()->subMinutes(1);
            }
            $this->createComment($ticket, $requester, $requesterCommentAt, 'Thanks for the update.');
        }

        $sla = $this->slaService->evaluate($ticket->fresh('statusChanges'));
        $ticket->forceFill([
            'sla_first_response_breached' => $sla['first_response_breached'],
            'sla_resolution_breached' => $sla['resolution_breached'],
        ])->saveQuietly();

        return $ticket;
    }

    protected function createComment(Ticket $ticket, User $user, Carbon $at, string $body): void
    {
        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_private' => false,
        ]);

        $comment->forceFill([
            'created_at' => $at->copy(),
            'updated_at' => $at->copy(),
        ])->saveQuietly();
    }

    protected function bucketTicketCount(string $departmentSlug, Carbon $bucket): int
    {
        $base = match ($departmentSlug) {
            'inbound' => 2,
            'outbound' => 2,
            'customer-returns', 'kariba' => 1,
            'icqa', 'support' => 1,
            'tom' => 1,
            default => 1,
        };

        $ratio = $this->seededRatio("demo-sla-count:{$departmentSlug}:{$bucket->format('YmdHi')}");

        if ($base === 2 && $ratio > 0.78) {
            return 3;
        }

        if ($base === 1 && $ratio < 0.28) {
            return 0;
        }

        return $base;
    }

    protected function determineFinalStatus(string $simulationKey): string
    {
        $ratio = $this->seededRatio($simulationKey . ':final-status');

        return match (true) {
            $ratio < 0.12 => TicketStatus::Triaged->value,
            $ratio < 0.32 => TicketStatus::InProgress->value,
            $ratio < 0.44 => TicketStatus::WaitingEmployee->value,
            $ratio < 0.50 => TicketStatus::Reopened->value,
            $ratio < 0.84 => TicketStatus::Resolved->value,
            default => TicketStatus::Closed->value,
        };
    }

    protected function pickPriority(string $simulationKey): string
    {
        $ratio = $this->seededRatio($simulationKey . ':priority');

        return match (true) {
            $ratio < 0.06 => 'critical',
            $ratio < 0.24 => 'high',
            $ratio < 0.78 => 'medium',
            default => 'low',
        };
    }

    protected function pickUser(Collection $users, string $seed): ?User
    {
        if ($users->isEmpty()) {
            return null;
        }

        return $users->values()->get($this->seededInt($seed, 0, $users->count() - 1));
    }

    protected function pickCategory(Collection $categories, string $seed): ?Category
    {
        if ($categories->isEmpty()) {
            return null;
        }

        return $categories->values()->get($this->seededInt($seed, 0, $categories->count() - 1));
    }

    protected function midpointTime(Carbon $start, Carbon $end, float $progress): Carbon
    {
        $minutes = max(1, $start->diffInMinutes($end, false));

        return $start->copy()->addMinutes((int) max(1, floor($minutes * $progress)));
    }

    protected function ticketTitle(Department $department, Category $category, Carbon $bucket, int $slot): string
    {
        return sprintf(
            '%s %s issue %s-%d',
            strtoupper($department->ops_code ?: Str::limit($department->slug, 3, '')),
            $category->name,
            $bucket->format('mdHi'),
            $slot + 1
        );
    }

    protected function ticketDescription(Department $department, Category $category, Carbon $createdAt, int $slot): string
    {
        return sprintf(
            'Simulated %s ticket for %s created at %s to keep SLA dashboards current (%d).',
            strtolower($category->name),
            $department->name,
            $createdAt->format('D H:i'),
            $slot + 1
        );
    }

    protected function ticketLocation(Department $department, string $seed): string
    {
        $locations = [
            'Gate B',
            'Inbound Dock',
            'Outbound Lane',
            'Canteen',
            $department->name . ' Floor',
        ];

        return $locations[$this->seededInt($seed . ':location', 0, count($locations) - 1)];
    }

    protected function alignToQuarterHour(Carbon $value): Carbon
    {
        return $value->copy()->setMinute((int) (floor($value->minute / 15) * 15))->setSecond(0);
    }

    protected function seededRatio(string $seed): float
    {
        return (float) sprintf('%u', crc32($seed)) / 4294967295;
    }

    protected function seededInt(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return $min + (int) floor($this->seededRatio($seed) * (($max - $min) + 1));
    }
}
