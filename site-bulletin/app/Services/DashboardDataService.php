<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\ManagerRelationship;
use App\Models\PerformanceSnapshot;
use App\Models\PerformanceSample;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardDataService
{
    public function __construct(
        protected PerformanceService $performanceService,
        protected DepartmentAnalyticsService $departmentAnalytics,
        protected DemoOperationsSimulationService $demoOperationsSimulation,
        protected RoleScopeService $roleScope,
        protected RoleActionService $roleActionService,
        protected OperationalPreventionService $operationalPrevention,
        protected HrWorkspaceService $hrWorkspaceService
    ) {
    }

    public function buildDashboardData(Request $request): array
    {
        $user = $request->user();

        if ($user && config('site_bulletin.demo_simulation_enabled')) {
            $this->demoOperationsSimulation->ensureFreshSamples();
        }

        $announcements = Announcement::query()
            ->with(['author', 'department'])
            ->active()
            ->visibleTo($user)
            ->ordered()
            ->take(6)
            ->get();

        $categories = Category::query()
            ->with([
                'links' => fn ($query) => $query->active()->orderBy('order'),
                'department',
            ])
            ->whereHas('links', fn ($query) => $query->active())
            ->visibleTo($user)
            ->orderBy('order')
            ->get();

        $snapshots = collect();
        $riskFlag = false;
        $messagePreview = collect();
        $unreadConversationCount = 0;
        $governanceLogs = collect();
        $departmentMetricTrend = collect();
        $managerBenchmark = null;
        $managerOverview = null;
        $managerHealthSummary = null;
        $managerSlaHealth = null;
        $managerSlaTimeline = collect();
        $managerTicketTypeBreakdown = collect();
        $managerAttentionQueue = null;
        $managerPreventionInsights = null;
        $managerTrendWindow = null;
        $managerScale = '7d';
        $employeeScale = '7d';
        $employeeWorkToday = null;
        $employeeOverview = null;
        $newsAnnouncements = collect();
        $unreadAnnouncementCount = 0;
        $highPriorityAnnouncementCount = 0;
        $roleActions = collect();
        $hrWorkspace = null;

        if ($user && $user->isEmployee()) {
            $snapshots = $user->performanceSnapshots()->recent()->orderByDesc('week_start')->get();
            $riskFlag = $this->performanceService->riskFlag($snapshots);
        }

        if ($user) {
            $newsAnnouncements = Announcement::query()
                ->with(['author:id,name', 'department:id,name'])
                ->active()
                ->visibleTo($user)
                ->ordered()
                ->withExists([
                    'readers as is_read' => fn ($query) => $query->where('users.id', $user->id),
                ])
                ->take(5)
                ->get();

            $unreadAnnouncementCount = Announcement::query()
                ->active()
                ->visibleTo($user)
                ->whereDoesntHave('readers', fn ($query) => $query->where('users.id', $user->id))
                ->count();

            $highPriorityAnnouncementCount = Announcement::query()
                ->active()
                ->visibleTo($user)
                ->whereIn('priority', ['high', 'urgent'])
                ->count();

            $messagePreview = Conversation::query()
                ->forUser($user)
                ->with([
                    'participants:id,name',
                    'messages' => fn ($query) => $query->latest()->with('sender:id,name')->limit(1),
                ])
                ->orderByDesc('updated_at')
                ->take(3)
                ->get()
                ->map(function (Conversation $conversation) use ($user) {
                    $conversation->unread_count = $conversation->unreadCountFor($user);
                    return $conversation;
                });

            $unreadConversationCount = Conversation::query()
                ->forUser($user)
                ->whereHas('participants', function ($query) use ($user) {
                    $query->where('users.id', $user->id)
                        ->where(function ($sub) {
                            $sub->whereNull('conversation_participants.last_read_at')
                                ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.updated_at');
                        });
                })
                ->count();

            if ($user->isEmployee()) {
                $employeeScale = in_array($request->query('scale'), ['7d', '24h', '3h'], true)
                    ? (string) $request->query('scale')
                    : '7d';

                $employeeWorkToday = $this->buildEmployeeWorkToday(
                    $user,
                    $snapshots,
                    $unreadAnnouncementCount,
                    $unreadConversationCount
                );

                $employeeOverview = $this->buildEmployeeOverview($user, $snapshots, $employeeWorkToday, $employeeScale);
            }

            $roleActions = $this->roleActionService->forUser($user, $snapshots);

            if ($user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
                $viewableDepartmentIds = $this->roleScope->viewableDepartmentIds($user);

                $governanceLogs = AuditLog::query()
                    ->with('actor:id,name')
                    ->when($viewableDepartmentIds !== null, function ($query) use ($viewableDepartmentIds, $user) {
                        $query->where(function ($inner) use ($viewableDepartmentIds, $user) {
                            $inner->where('actor_id', $user->id);

                            if ($viewableDepartmentIds->isNotEmpty()) {
                                $inner->orWhereHasMorph('auditable', [Ticket::class], fn ($ticketQuery) => $ticketQuery
                                    ->whereIn('department_id', $viewableDepartmentIds));
                            }
                        });
                    })
                    ->latest('occurred_at')
                    ->take(5)
                    ->get();
            }

            if ($user->hasRole('hr')) {
                $hrWorkspace = $this->hrWorkspaceService->build();
            }

            if ($user->hasRole('manager', 'ops_manager')) {
                $managerScale = in_array($request->query('scale'), ['7d', '24h', '3h'], true)
                    ? (string) $request->query('scale')
                    : '7d';

                if (! DepartmentMetric::query()->whereDate('metric_date', Carbon::today()->toDateString())->exists()) {
                    $this->departmentAnalytics->recalculateForDate(Carbon::now());
                }

                $scopedDepartmentId = $this->resolveManagerScopeDepartmentId($user);
                $primaryDepartment = $scopedDepartmentId ? Department::query()->find($scopedDepartmentId) : null;
                $this->departmentAnalytics->ensureRecentWindow($scopedDepartmentId, 7);

                $departmentMetricTrend = DepartmentMetric::query()
                    ->where('department_id', $scopedDepartmentId)
                    ->orderByDesc('metric_date')
                    ->take(7)
                    ->get()
                    ->reverse()
                    ->values();

                if ($departmentMetricTrend->isEmpty()) {
                    $departmentMetricTrend = DepartmentMetric::query()
                        ->whereNull('department_id')
                        ->orderByDesc('metric_date')
                        ->take(7)
                        ->get()
                        ->reverse()
                        ->values();
                }

                $managerOverview = $this->buildManagerOverview($scopedDepartmentId, $primaryDepartment, $departmentMetricTrend, $managerScale);
                $managerSlaHealth = $this->buildManagerSlaHealth($scopedDepartmentId);
                $managerSlaTimeline = $this->buildManagerSlaTimeline($scopedDepartmentId, 7);
                $managerHealthSummary = $this->buildManagerHealthSummary($managerOverview, $managerSlaHealth);
                $managerAttentionQueue = $this->buildManagerAttentionQueue($user, $scopedDepartmentId);
                $managerPreventionInsights = $this->operationalPrevention->forUser(
                    $user,
                    14,
                    $user->hasRole('ops_manager') ? null : $scopedDepartmentId
                );
                $managerTrendWindow = $this->resolveScaleWindow($managerScale);
                $managerTicketTypeBreakdown = $this->buildManagerTicketTypeBreakdown($scopedDepartmentId);

                $latestDepartmentMetric = DepartmentMetric::query()
                    ->where('department_id', $scopedDepartmentId)
                    ->latest('metric_date')
                    ->first();

                $latestCompanyMetric = DepartmentMetric::query()
                    ->whereNull('department_id')
                    ->latest('metric_date')
                    ->first();

                if (! $latestCompanyMetric && $latestDepartmentMetric) {
                    $latestCompanyMetric = DepartmentMetric::query()
                        ->whereNotNull('department_id')
                        ->whereDate('metric_date', $latestDepartmentMetric->metric_date->toDateString())
                        ->selectRaw('AVG(avg_resolution_minutes) as avg_resolution_minutes, AVG(open_tickets) as open_tickets, AVG(sla_breaches) as sla_breaches')
                        ->first();
                }

                if ($latestDepartmentMetric && $latestCompanyMetric) {
                    $windowStart = Carbon::now()->subDays(30);
                    $departmentClosedTickets = Ticket::query()
                        ->where('department_id', $scopedDepartmentId)
                        ->whereNotNull('closed_at')
                        ->where('closed_at', '>=', $windowStart)
                        ->get(['created_at', 'closed_at']);

                    $companyClosedTickets = Ticket::query()
                        ->whereNotNull('closed_at')
                        ->where('closed_at', '>=', $windowStart)
                        ->get(['created_at', 'closed_at']);

                    $departmentResolutionHours = $this->clampResolutionHours(
                        $this->avgClosedResolutionHours($departmentClosedTickets)
                        ?? ($latestDepartmentMetric->avg_resolution_minutes ? ($latestDepartmentMetric->avg_resolution_minutes / 60) : null)
                    );
                    $companyResolutionHours = $this->clampResolutionHours(
                        $this->avgClosedResolutionHours($companyClosedTickets)
                        ?? ($latestCompanyMetric->avg_resolution_minutes ? ($latestCompanyMetric->avg_resolution_minutes / 60) : null)
                    );
                    $industryResolutionHours = 6.8;

                    $departmentSlaAdherence = $this->calculateSlaAdherence(
                        (int) $latestDepartmentMetric->open_tickets,
                        (int) $latestDepartmentMetric->sla_breaches
                    );
                    $companySlaAdherence = $this->calculateSlaAdherence(
                        (int) $latestCompanyMetric->open_tickets,
                        (int) $latestCompanyMetric->sla_breaches
                    );
                    $industrySlaAdherence = 85.0;

                    $managerBenchmark = [
                        'resolution' => [
                            'department' => $departmentResolutionHours,
                            'company' => $companyResolutionHours,
                            'industry' => $industryResolutionHours,
                        ],
                        'sla' => [
                            'department' => $departmentSlaAdherence,
                            'company' => $companySlaAdherence,
                            'industry' => $industrySlaAdherence,
                        ],
                    ];
                }
            }
        }

        return [
            'announcements' => $announcements,
            'categories' => $categories,
            'snapshots' => $snapshots,
            'riskFlag' => $riskFlag,
            'messagePreview' => $messagePreview,
            'unreadConversationCount' => $unreadConversationCount,
            'governanceLogs' => $governanceLogs,
            'departmentMetricTrend' => $departmentMetricTrend,
            'managerBenchmark' => $managerBenchmark,
            'managerOverview' => $managerOverview,
            'managerHealthSummary' => $managerHealthSummary,
            'managerSlaHealth' => $managerSlaHealth,
            'managerSlaTimeline' => $managerSlaTimeline,
            'managerTicketTypeBreakdown' => $managerTicketTypeBreakdown,
            'managerAttentionQueue' => $managerAttentionQueue,
            'managerPreventionInsights' => $managerPreventionInsights,
            'managerTrendWindow' => $managerTrendWindow,
            'managerScale' => $managerScale,
            'employeeWorkToday' => $employeeWorkToday,
            'employeeOverview' => $employeeOverview,
            'employeeScale' => $employeeScale,
            'newsAnnouncements' => $newsAnnouncements,
            'unreadAnnouncementCount' => $unreadAnnouncementCount,
            'highPriorityAnnouncementCount' => $highPriorityAnnouncementCount,
            'roleActions' => $roleActions,
            'hrWorkspace' => $hrWorkspace,
        ];
    }

    public function buildMyWorkData(Request $request): array
    {
        $user = $request->user();

        if (config('site_bulletin.demo_simulation_enabled')) {
            $this->demoOperationsSimulation->ensureFreshSamples();
        }

        $snapshots = $user->performanceSnapshots()->recent()->orderByDesc('week_start')->get();
        $riskFlag = $this->performanceService->riskFlag($snapshots);
        $employeeScale = in_array($request->query('scale'), ['7d', '24h', '3h'], true)
            ? (string) $request->query('scale')
            : '7d';

        $unreadAnnouncementCount = Announcement::query()
            ->active()
            ->visibleTo($user)
            ->whereDoesntHave('readers', fn ($query) => $query->where('users.id', $user->id))
            ->count();

        $unreadConversationCount = Conversation::query()
            ->forUser($user)
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                    ->where(function ($sub) {
                        $sub->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.updated_at');
                    });
            })
            ->count();

        $employeeWorkToday = $this->buildEmployeeWorkToday(
            $user,
            $snapshots,
            $unreadAnnouncementCount,
            $unreadConversationCount
        );

        $employeeOverview = $this->buildEmployeeOverview($user, $snapshots, $employeeWorkToday, $employeeScale);

        return [
            'snapshots' => $snapshots,
            'riskFlag' => $riskFlag,
            'employeeWorkToday' => $employeeWorkToday,
            'employeeOverview' => $employeeOverview,
            'employeeScale' => $employeeScale,
        ];
    }

    protected function calculateSlaAdherence(int $openTickets, int $breaches): float
    {
        if ($openTickets <= 0) {
            return 100.0;
        }

        return round(max(0, min(100, 100 - (($breaches / $openTickets) * 100))), 1);
    }

    protected function buildManagerOverview(?int $departmentId, ?Department $department, $departmentMetricTrend, string $scale = '7d'): ?array
    {
        if (! $departmentId || $departmentMetricTrend->isEmpty()) {
            return null;
        }

        $departmentUserIds = $this->departmentUserIds($departmentId);
        $observedTeamSize = User::query()
            ->where('role', 'employee')
            ->where(function ($query) use ($departmentId) {
                $query->where('primary_department_id', $departmentId)
                    ->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery
                        ->where('departments.id', $departmentId));
            })
            ->count();

        $profile = $this->resolveDepartmentProfile($department);
        $shift = $this->resolveActiveShift($profile, now());
        $plannedTeamSize = max((int) ($profile['planned_headcount'] ?? 0), $observedTeamSize);

        $targetProductivity = (float) $profile['target_units_per_hour'];
        $targetQuality = (float) $profile['target_quality'];
        $seriesByScale = $this->buildSampleSeriesForUsers($departmentUserIds);

        if (collect($seriesByScale['7d'] ?? [])->isEmpty()) {
            $baseSeries = $departmentMetricTrend->values()->map(function (DepartmentMetric $metric, int $index) use ($departmentId, $profile, $departmentMetricTrend) {
                $count = max(1, $departmentMetricTrend->count() - 1);
                $position = $count > 0 ? ($index / $count) : 0;
                $wave = sin(($position * M_PI) + 0.35);

                $productivityFactor = 0.90 + ($wave * 0.08) + $this->seededFloat("daily-prod-{$departmentId}-{$metric->metric_date?->toDateString()}", -0.04, 0.05);
                $qualityFactor = 0.97 + ($wave * 0.02) + $this->seededFloat("daily-quality-{$departmentId}-{$metric->metric_date?->toDateString()}", -0.015, 0.01);

                $productivity = round(max(8, min(80, $profile['target_units_per_hour'] * $productivityFactor)), 1);
                $quality = round(max(70, min(100, $profile['target_quality'] * $qualityFactor)), 1);

                return [
                    'label' => $metric->metric_date?->format('D') ?? '-',
                    'productivity' => $productivity,
                    'quality' => $quality,
                    'from_date' => $metric->metric_date?->toDateString(),
                    'to_date' => $metric->metric_date?->toDateString(),
                ];
            })->values();

            $seriesByScale = [
                '7d' => $baseSeries,
                '24h' => $this->buildHourlySeries($departmentId, $profile, 8, 3),
                '3h' => $this->buildHourlySeries($departmentId, $profile, 7, 0.5),
            ];
        }

        $activeSeries = $seriesByScale[$scale] ?? $seriesByScale['7d'];
        $currentPoint = collect($seriesByScale['24h'] ?? [])->last() ?: collect($seriesByScale['7d'] ?? [])->last();
        $shiftWindowEnd = now()->lessThan($shift['end']) ? now() : $shift['end'];
        $sampledActiveEmployees = $this->sampledUserCountForWindow($departmentUserIds, $shift['start'], $shiftWindowEnd);
        $activeEmployees = max(1, $sampledActiveEmployees ?: $observedTeamSize);
        $elapsedHoursForRate = max(0.5, $shift['elapsed_hours']);
        $avgProductivity = round((float) ($currentPoint['productivity'] ?? $targetProductivity), 1);
        $qualityScore = round((float) ($currentPoint['quality'] ?? $targetQuality), 1);
        $shiftTargetUnits = (int) round($activeEmployees * $targetProductivity * $shift['duration_hours']);
        $sampledUnits = $this->sumSampledUnitsForWindow($departmentUserIds, $shift['start'], $shiftWindowEnd);
        $actualUnits = $sampledUnits > 0
            ? $sampledUnits
            : (int) round($avgProductivity * $activeEmployees * $elapsedHoursForRate);
        $productivityAchievementPct = $targetProductivity > 0
            ? round(($avgProductivity / $targetProductivity) * 100, 1)
            : 0.0;
        $qualityAchievementPct = $targetQuality > 0
            ? round(($qualityScore / $targetQuality) * 100, 1)
            : 0.0;

        return [
            'department_id' => $departmentId,
            'department_code' => $profile['code'],
            'department_description' => $profile['description'] ?? null,
            'team_size' => $activeEmployees,
            'planned_team_size' => $plannedTeamSize,
            'units_processed' => $actualUnits,
            'avg_productivity' => $avgProductivity,
            'quality_score' => $qualityScore,
            'target_productivity' => $targetProductivity,
            'target_quality' => $targetQuality,
            'productivity_achievement_pct' => $productivityAchievementPct,
            'quality_achievement_pct' => $qualityAchievementPct,
            'productivity_status' => $this->scoreBand($productivityAchievementPct),
            'quality_status' => $this->scoreBand($qualityAchievementPct),
            'shift_name' => $shift['label'],
            'shift_start' => $shift['start'],
            'shift_end' => $shift['end'],
            'shift_duration_hours' => $shift['duration_hours'],
            'shift_elapsed_hours' => $shift['elapsed_hours'],
            'shift_target_units' => $shiftTargetUnits,
            'series' => $activeSeries,
            'series_by_scale' => $seriesByScale,
            'active_scale' => $scale,
        ];
    }

    protected function buildHourlySeries(int $departmentId, array $profile, int $count, float $hoursStep): \Illuminate\Support\Collection
    {
        $start = now()->subHours(($count - 1) * $hoursStep);

        return collect(range(0, $count - 1))->map(function (int $index) use ($departmentId, $profile, $start, $hoursStep, $count) {
            $time = $start->copy()->addMinutes((int) round($index * $hoursStep * 60));
            $progress = $count > 1 ? ($index / ($count - 1)) : 0;
            $productivityFactor = $this->buildPerformanceFactor($departmentId, $progress, "intraday-{$time->format('YmdHi')}");
            $qualityFactor = $this->buildQualityFactor($departmentId, $progress, "intraday-{$time->format('YmdHi')}");

            $productivity = round(max(8, min(80, $profile['target_units_per_hour'] * $productivityFactor)), 1);
            $quality = round(max(70, min(100, $profile['target_quality'] * $qualityFactor)), 1);

            $label = $hoursStep >= 1
                ? $time->format('H:i')
                : $time->format('H:i');

            return [
                'label' => $label,
                'productivity' => $productivity,
                'quality' => $quality,
                'from_date' => $time->toDateString(),
                'to_date' => $time->toDateString(),
            ];
        });
    }

    protected function avgClosedResolutionHours($tickets): ?float
    {
        $minutes = $tickets->map(function ($ticket) {
            if (! $ticket->created_at || ! $ticket->closed_at) {
                return null;
            }

            return $ticket->created_at->diffInMinutes($ticket->closed_at, false);
        })->filter(fn ($value) => is_numeric($value) && $value >= 0 && $value <= 4320);

        if ($minutes->isEmpty()) {
            return null;
        }

        return round($minutes->avg() / 60, 1);
    }

    protected function clampResolutionHours(?float $hours): ?float
    {
        if ($hours === null) {
            return null;
        }

        return round(max(0.1, min(72.0, $hours)), 1);
    }

    protected function buildManagerSlaHealth(?int $departmentId): ?array
    {
        if (! $departmentId) {
            return null;
        }

        $openTickets = Ticket::query()
            ->where('department_id', $departmentId)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->get(['sla_first_response_breached', 'sla_resolution_breached']);

        $open = $openTickets->count();

        if ($open === 0) {
            return [
                'within' => 100.0,
                'at_risk' => 0.0,
                'breached' => 0.0,
                'open_tickets' => 0,
                'within_count' => 0,
                'at_risk_count' => 0,
                'breached_count' => 0,
            ];
        }

        $breached = $openTickets->filter(function (Ticket $ticket) {
            return (bool) ($ticket->sla_first_response_breached ?? false)
                || (bool) ($ticket->sla_resolution_breached ?? false);
        })->count();

        $nonBreached = max(0, $open - $breached);
        $atRisk = (int) min($nonBreached, max(0, round($nonBreached * 0.15)));
        $within = max(0, $nonBreached - $atRisk);

        $toPercent = fn (int $value) => round(($value / $open) * 100, 1);

        return [
            'within' => $toPercent($within),
            'at_risk' => $toPercent($atRisk),
            'breached' => $toPercent($breached),
            'open_tickets' => $open,
            'within_count' => $within,
            'at_risk_count' => $atRisk,
            'breached_count' => $breached,
        ];
    }

    protected function buildManagerHealthSummary(?array $managerOverview, ?array $managerSlaHealth): ?array
    {
        if (! $managerOverview || ! $managerSlaHealth) {
            return null;
        }

        $performancePct = (float) ($managerOverview['productivity_achievement_pct'] ?? 0.0);

        return [
            'performance_pct' => $performancePct,
            'quality_score' => (float) $managerOverview['quality_score'],
            'within_sla_pct' => (float) $managerSlaHealth['within'],
        ];
    }

    protected function buildManagerSlaTimeline(?int $departmentId, int $days = 7): \Illuminate\Support\Collection
    {
        if (! $departmentId || $days < 1) {
            return collect();
        }

        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $tickets = Ticket::query()
            ->where('department_id', $departmentId)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->whereBetween('updated_at', [$start, $end])
            ->get(['updated_at', 'status', 'sla_first_response_breached', 'sla_resolution_breached']);

        return collect(range(0, $days - 1))->map(function (int $offset) use ($start, $tickets) {
            $date = $start->copy()->addDays($offset);
            $dayTickets = $tickets->filter(
                fn (Ticket $ticket) => $ticket->updated_at && $ticket->updated_at->isSameDay($date)
            );

            $total = $dayTickets->count();
            $breached = $dayTickets->filter(function (Ticket $ticket) {
                return (bool) ($ticket->sla_first_response_breached ?? false)
                    || (bool) ($ticket->sla_resolution_breached ?? false);
            })->count();

            $open = $dayTickets->count();

            return [
                'metric_date' => $date,
                'total_tickets' => $total,
                'breached_tickets' => $breached,
                'within_tickets' => max(0, $total - $breached),
                'open_tickets' => $open,
            ];
        });
    }

    protected function resolveDepartmentProfile(?Department $department): array
    {
        if ($department) {
            return [
                'code' => $department->ops_code ?: 'OPS',
                'target_units_per_hour' => (float) ($department->target_units_per_hour ?: 42.0),
                'target_quality' => (float) ($department->target_quality_pct ?: 95.0),
                'planned_headcount' => (int) ($department->planned_headcount ?: 10),
                'description' => $department->description ?: 'General operations profile.',
                'day_start' => $department->day_shift_start ? $department->day_shift_start->format('H:i') : '10:00',
                'day_end' => $department->day_shift_end ? $department->day_shift_end->format('H:i') : '20:00',
                'night_start' => $department->night_shift_start ? $department->night_shift_start->format('H:i') : '18:30',
                'night_end' => $department->night_shift_end ? $department->night_shift_end->format('H:i') : '04:45',
            ];
        }

        return [
            'code' => 'OPS',
            'target_units_per_hour' => 42.0,
            'target_quality' => 95.0,
            'planned_headcount' => 10,
            'description' => 'General operations profile.',
            'day_start' => '10:00',
            'day_end' => '20:00',
            'night_start' => '18:30',
            'night_end' => '04:45',
        ];
    }

    protected function resolveActiveShift(array $profile, Carbon $now): array
    {
        $dayStart = $now->copy()->setTimeFromTimeString($profile['day_start']);
        $dayEnd = $now->copy()->setTimeFromTimeString($profile['day_end']);

        $nightStartToday = $now->copy()->setTimeFromTimeString($profile['night_start']);
        $nightEndToday = $now->copy()->setTimeFromTimeString($profile['night_end']);
        $nightStartYesterday = $nightStartToday->copy()->subDay();
        $nightEndTomorrow = $nightEndToday->copy()->addDay();

        if ($now->betweenIncluded($dayStart, $dayEnd)) {
            $start = $dayStart;
            $end = $dayEnd;
            $code = 'day';
            $label = 'Day Shift';
        } elseif ($now->greaterThanOrEqualTo($nightStartToday)) {
            $start = $nightStartToday;
            $end = $nightEndTomorrow;
            $code = 'night';
            $label = 'Night Shift';
        } elseif ($now->lessThanOrEqualTo($nightEndToday)) {
            $start = $nightStartYesterday;
            $end = $nightEndToday;
            $code = 'night';
            $label = 'Night Shift';
        } else {
            $start = $dayStart;
            $end = $dayEnd;
            $code = 'day';
            $label = 'Day Shift';
        }

        $durationHours = round($start->diffInMinutes($end) / 60, 2);
        $elapsedHours = round(max(0, min($durationHours, $start->diffInMinutes($now, false) / 60)), 2);

        return [
            'code' => $code,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'duration_hours' => $durationHours,
            'elapsed_hours' => $elapsedHours,
        ];
    }

    protected function buildPerformanceFactor(int $departmentId, float $progress, string $salt = 'shift'): float
    {
        $earlyBoost = max(0, 0.08 - ($progress * 0.12));
        $endDrop = max(0, ($progress - 0.68) / 0.32) * 0.13;
        $variance = $this->seededFloat("perf-{$salt}-{$departmentId}-" . (int) round($progress * 100), -0.05, 0.06);

        return max(0.68, min(1.15, 1.0 + $earlyBoost - $endDrop + $variance));
    }

    protected function buildQualityFactor(int $departmentId, float $progress, string $salt = 'shift'): float
    {
        $earlyBoost = max(0, 0.03 - ($progress * 0.04));
        $endDrop = max(0, ($progress - 0.7) / 0.3) * 0.06;
        $variance = $this->seededFloat("quality-{$salt}-{$departmentId}-" . (int) round($progress * 100), -0.02, 0.015);

        return max(0.88, min(1.04, 1.0 + $earlyBoost - $endDrop + $variance));
    }

    protected function seededFloat(string $seed, float $min, float $max): float
    {
        $hash = (float) sprintf('%u', crc32($seed));
        $ratio = $hash / 4294967295;

        return $min + (($max - $min) * $ratio);
    }

    protected function scoreBand(float $pct): string
    {
        if ($pct >= 90) {
            return 'green';
        }

        if ($pct >= 70) {
            return 'amber';
        }

        return 'red';
    }

    protected function resolveScaleWindow(string $scale): array
    {
        $end = now();

        $start = match ($scale) {
            '24h' => $end->copy()->subHours(24),
            '3h' => $end->copy()->subHours(3),
            default => $end->copy()->subDays(7),
        };

        $label = match ($scale) {
            '24h' => 'Last 24 hours',
            '3h' => 'Last 3 hours',
            default => 'Last 7 days',
        };

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    protected function resolveManagerScopeDepartmentId(User $user): ?int
    {
        $managedDepartmentIds = $this->roleScope->manageableDepartmentIds($user);

        if ($managedDepartmentIds === null) {
            return $user->primary_department_id
                ?: Department::query()->orderBy('name')->value('id');
        }

        if ($managedDepartmentIds->isEmpty()) {
            return null;
        }

        if ($user->primary_department_id && $managedDepartmentIds->contains($user->primary_department_id)) {
            return $user->primary_department_id;
        }

        return $managedDepartmentIds->first();
    }

    protected function buildEmployeeWorkToday(User $user, $snapshots, int $unreadAnnouncementCount, int $unreadConversationCount): ?array
    {
        $department = $user->primaryDepartment ?: $user->departments()->first();
        $profile = $this->resolveDepartmentProfile($department);
        $shift = $this->resolveActiveShift($profile, now());
        $recentSamples = PerformanceSample::query()
            ->where('user_id', $user->id)
            ->where('recorded_at', '>=', now()->subHours(2))
            ->orderBy('recorded_at')
            ->get();

        $currentWindow = $recentSamples->where('recorded_at', '>=', now()->subHour())->values();
        $previousWindow = $recentSamples
            ->where('recorded_at', '<', now()->subHour())
            ->where('recorded_at', '>=', now()->subHours(2))
            ->values();

        $latestSnapshot = $snapshots->first();
        $previousSnapshot = $snapshots->skip(1)->first();
        $latestUnits = $currentWindow->isNotEmpty()
            ? round((float) $currentWindow->avg('units_per_hour'), 1)
            : ($latestSnapshot?->units_per_hour !== null ? round((float) $latestSnapshot->units_per_hour, 1) : null);
        $previousUnits = $previousWindow->isNotEmpty()
            ? round((float) $previousWindow->avg('units_per_hour'), 1)
            : ($previousSnapshot?->units_per_hour !== null ? round((float) $previousSnapshot->units_per_hour, 1) : null);
        $latestQuality = $currentWindow->isNotEmpty()
            ? round((float) $currentWindow->avg('quality_score'), 1)
            : ($latestSnapshot?->quality_score !== null
                ? round((float) $latestSnapshot->quality_score, 1)
                : ($latestSnapshot?->rank_percentile !== null ? round(max(0, min(100, 100 - (float) $latestSnapshot->rank_percentile)), 1) : null));
        $previousQuality = $previousWindow->isNotEmpty()
            ? round((float) $previousWindow->avg('quality_score'), 1)
            : ($previousSnapshot?->quality_score !== null
                ? round((float) $previousSnapshot->quality_score, 1)
                : ($previousSnapshot?->rank_percentile !== null ? round(max(0, min(100, 100 - (float) $previousSnapshot->rank_percentile)), 1) : null));

        $openTickets = Ticket::query()
            ->with(['category:id,name', 'assignee:id,name', 'department:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('created_for_id', $user->id);
            })
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->orderByDesc('updated_at')
            ->get();

        $actionRequiredCount = $openTickets->where('status', TicketStatus::WaitingEmployee)->count();
        $breachedCount = $openTickets->filter(function (Ticket $ticket) {
            return (bool) ($ticket->sla_first_response_breached ?? false)
                || (bool) ($ticket->sla_resolution_breached ?? false);
        })->count();
        $unassignedCount = $openTickets->whereNull('assignee_id')->count();

        $managerName = ManagerRelationship::query()
            ->where('manager_id', $user->id)
            ->with('reportsTo:id,name')
            ->first()
            ?->reportsTo
            ?->name;

        $focusTickets = $openTickets->take(3)->map(function (Ticket $ticket) {
            return [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'status_label' => str($ticket->status?->value ?? 'new')->replace('_', ' ')->title()->toString(),
                'priority_label' => ucfirst($ticket->priority?->value ?? 'medium'),
                'category_name' => $ticket->category?->name ?? 'General',
                'assignee_name' => $ticket->assignee?->name,
                'updated_human' => $ticket->updated_at?->diffForHumans() ?? 'recently',
                'next_step' => $this->describeEmployeeTicketNextStep($ticket),
                'requires_action' => ($ticket->status === TicketStatus::WaitingEmployee),
                'is_breached' => (bool) ($ticket->sla_first_response_breached ?? false)
                    || (bool) ($ticket->sla_resolution_breached ?? false),
            ];
        });

        return [
            'department_name' => $department?->name ?? 'Your department',
            'department_code' => $profile['code'] ?? 'OPS',
            'department_description' => $profile['description'] ?? null,
            'manager_name' => $managerName,
            'shift_name' => $shift['label'],
            'shift_start' => $shift['start'],
            'shift_end' => $shift['end'],
            'target_units_per_hour' => (float) ($profile['target_units_per_hour'] ?? 42),
            'target_quality_pct' => (float) ($profile['target_quality'] ?? 95),
            'latest_units_per_hour' => $latestUnits,
            'latest_quality_score' => $latestQuality,
            'units_processed' => $this->sumSampledUnitsForWindow(
                [$user->id],
                $shift['start'],
                now()->lessThan($shift['end']) ? now() : $shift['end']
            ),
            'throughput_delta' => $latestUnits !== null && $previousUnits !== null
                ? round($latestUnits - $previousUnits, 1)
                : null,
            'quality_delta' => $latestQuality !== null && $previousQuality !== null
                ? round($latestQuality - $previousQuality, 1)
                : null,
            'open_ticket_count' => $openTickets->count(),
            'action_required_count' => $actionRequiredCount,
            'breached_ticket_count' => $breachedCount,
            'unassigned_ticket_count' => $unassignedCount,
            'unread_announcement_count' => $unreadAnnouncementCount,
            'unread_message_count' => $unreadConversationCount,
            'focus_tickets' => $focusTickets,
        ];
    }

    protected function buildEmployeeOverview(User $user, $snapshots, ?array $employeeWorkToday, string $scale = '7d'): ?array
    {
        if (! $employeeWorkToday) {
            return null;
        }

        $targetProductivity = (float) ($employeeWorkToday['target_units_per_hour'] ?? 42.0);
        $targetQuality = (float) ($employeeWorkToday['target_quality_pct'] ?? 95.0);
        $seriesByScale = $this->buildSampleSeriesForUsers([$user->id]);

        if (empty($seriesByScale['7d']) || collect($seriesByScale['7d'])->isEmpty()) {
            $seriesByScale['7d'] = collect($snapshots)
                ->sortBy('week_start')
                ->values()
                ->map(function (PerformanceSnapshot $snapshot) {
                    $quality = $snapshot->quality_score;

                    if ($quality === null && $snapshot->rank_percentile !== null) {
                        $quality = max(0, min(100, 100 - (float) $snapshot->rank_percentile));
                    }

                    return [
                        'label' => $snapshot->week_start?->format('M j') ?? '-',
                        'productivity' => (float) ($snapshot->units_per_hour ?? 0),
                        'quality' => round((float) ($quality ?? 0), 1),
                        'from_date' => $snapshot->week_start?->toDateString(),
                        'to_date' => $snapshot->week_start?->toDateString(),
                    ];
                });
        }

        if (empty($seriesByScale['24h']) || collect($seriesByScale['24h'])->isEmpty()) {
            $seriesByScale['24h'] = collect($seriesByScale['7d'])->take(-8)->values();
        }

        if (empty($seriesByScale['3h']) || collect($seriesByScale['3h'])->isEmpty()) {
            $seriesByScale['3h'] = collect($seriesByScale['24h'])->take(-7)->values();
        }

        if (collect($seriesByScale['7d'])->isEmpty()) {
            return null;
        }

        $activeSeries = $seriesByScale[$scale] ?? $seriesByScale['7d'];

        return [
            'target_productivity' => $targetProductivity,
            'target_quality' => $targetQuality,
            'series_by_scale' => $seriesByScale,
            'series' => $activeSeries,
            'active_scale' => $scale,
            'peak_productivity' => round((float) $activeSeries->max('productivity'), 1),
            'latest_quality' => round((float) ($activeSeries->last()['quality'] ?? 0), 1),
            'best_quality' => round((float) $activeSeries->max('quality'), 1),
        ];
    }

    protected function buildSampleSeriesForUsers(array $userIds): array
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        if (empty($userIds)) {
            return [
                '7d' => collect(),
                '24h' => collect(),
                '3h' => collect(),
            ];
        }

        $samples = PerformanceSample::query()
            ->whereIn('user_id', $userIds)
            ->where('recorded_at', '>=', now()->subDays(7))
            ->orderBy('recorded_at')
            ->get();

        if ($samples->isEmpty()) {
            return [
                '7d' => collect(),
                '24h' => collect(),
                '3h' => collect(),
            ];
        }

        $seriesEnd = $samples->last()?->recorded_at?->copy() ?? now();
        $last24Start = $seriesEnd->copy()->subHours(24);
        $last3Start = $seriesEnd->copy()->subHours(3);

        $daily = $samples->groupBy(fn (PerformanceSample $sample) => $sample->recorded_at?->toDateString())
            ->take(-7)
            ->map(function ($group, $date) {
                $day = Carbon::parse($date);

                return [
                    'label' => $day->format('D'),
                    'productivity' => round((float) $group->avg('units_per_hour'), 1),
                    'quality' => round((float) $group->avg('quality_score'), 1),
                    'from_date' => $day->toDateString(),
                    'to_date' => $day->toDateString(),
                ];
            })
            ->values();

        $last24 = $samples->filter(fn (PerformanceSample $sample) => $sample->recorded_at && $sample->recorded_at->greaterThanOrEqualTo($last24Start))
            ->groupBy(function (PerformanceSample $sample) {
                $timestamp = $sample->recorded_at?->copy();

                if (! $timestamp) {
                    return null;
                }

                $hourBucket = (int) (floor($timestamp->hour / 3) * 3);

                return $timestamp->copy()->setTime($hourBucket, 0)->format('Y-m-d H:i:s');
            })
            ->filter()
            ->map(function ($group, $bucket) {
                $time = Carbon::parse($bucket);

                return [
                    'label' => $time->format('H:i'),
                    'productivity' => round((float) $group->avg('units_per_hour'), 1),
                    'quality' => round((float) $group->avg('quality_score'), 1),
                    'from_date' => $time->toDateString(),
                    'to_date' => $time->toDateString(),
                ];
            })
            ->values();

        $last3 = $samples->filter(fn (PerformanceSample $sample) => $sample->recorded_at && $sample->recorded_at->greaterThanOrEqualTo($last3Start))
            ->groupBy(fn (PerformanceSample $sample) => $sample->recorded_at?->copy()?->startOfMinute()->format('Y-m-d H:i:s'))
            ->filter()
            ->map(function ($group, $bucket) {
                $time = Carbon::parse($bucket);

                return [
                    'label' => $time->format('H:i'),
                    'productivity' => round((float) $group->avg('units_per_hour'), 1),
                    'quality' => round((float) $group->avg('quality_score'), 1),
                    'from_date' => $time->toDateString(),
                    'to_date' => $time->toDateString(),
                ];
            })
            ->values();

        return [
            '7d' => $daily,
            '24h' => $last24,
            '3h' => $last3,
        ];
    }

    protected function departmentUserIds(int $departmentId): array
    {
        return User::query()
            ->where('role', 'employee')
            ->where(function ($query) use ($departmentId) {
                $query->where('primary_department_id', $departmentId)
                    ->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery
                        ->where('departments.id', $departmentId));
            })
            ->pluck('id')
            ->all();
    }

    protected function sumSampledUnitsForWindow(array $userIds, Carbon $start, Carbon $end): int
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        if (empty($userIds) || $end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $totalUnits = PerformanceSample::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('recorded_at', [$start, $end])
            ->sum('units_per_hour');

        return (int) round(((float) $totalUnits) / 4);
    }

    protected function sampledUserCountForWindow(array $userIds, Carbon $start, Carbon $end): int
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        if (empty($userIds) || $end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return PerformanceSample::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('recorded_at', [$start, $end])
            ->distinct('user_id')
            ->count('user_id');
    }

    protected function describeEmployeeTicketNextStep(Ticket $ticket): string
    {
        return match ($ticket->status) {
            TicketStatus::WaitingEmployee => 'Action needed from you',
            TicketStatus::New => 'Waiting for triage',
            TicketStatus::Triaged => 'Acknowledged and queued for action',
            TicketStatus::InProgress => 'Being worked on now',
            TicketStatus::Reopened => 'Reopened and awaiting another update',
            default => 'Check the ticket thread for the latest update',
        };
    }

    protected function buildManagerTicketTypeBreakdown(?int $departmentId): \Illuminate\Support\Collection
    {
        if (! $departmentId) {
            return collect();
        }

        return Ticket::query()
            ->selectRaw('category_id, COUNT(*) as total, SUM(CASE WHEN sla_first_response_breached = 1 OR sla_resolution_breached = 1 THEN 1 ELSE 0 END) as breached')
            ->with('category:id,name')
            ->where('department_id', $departmentId)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(function (Ticket $ticket) {
                return [
                    'category_id' => $ticket->category_id,
                    'category_name' => $ticket->category?->name ?? 'Unknown',
                    'total' => (int) ($ticket->total ?? 0),
                    'breached' => (int) ($ticket->breached ?? 0),
                ];
            });
    }

    protected function buildManagerAttentionQueue(User $user, ?int $departmentId): ?array
    {
        if (! $departmentId) {
            return null;
        }

        $managedDepartmentIds = $this->roleScope->manageableDepartmentIds($user);

        $breachedTickets = Ticket::query()
            ->with(['requester:id,name', 'category:id,name'])
            ->where('department_id', $departmentId)
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->where(function ($query) {
                $query->where('sla_first_response_breached', true)
                    ->orWhere('sla_resolution_breached', true);
            })
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'requester_name' => $ticket->requester?->name,
                'category_name' => $ticket->category?->name ?? 'General',
                'updated_human' => $ticket->updated_at?->diffForHumans() ?? 'recently',
            ]);

        $waitingOnEmployees = Ticket::query()
            ->with(['requester:id,name', 'category:id,name'])
            ->where('department_id', $departmentId)
            ->where('status', TicketStatus::WaitingEmployee)
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'requester_name' => $ticket->requester?->name,
                'category_name' => $ticket->category?->name ?? 'General',
                'updated_human' => $ticket->updated_at?->diffForHumans() ?? 'recently',
            ]);

        $unreadConversations = Conversation::query()
            ->forUser($user)
            ->with([
                'participants:id,name',
                'messages' => fn ($query) => $query->latest()->with('sender:id,name')->limit(1),
            ])
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                    ->where(function ($sub) {
                        $sub->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.updated_at');
                    });
            })
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(function (Conversation $conversation) use ($user) {
                $others = $conversation->participants
                    ->where('id', '!=', $user->id)
                    ->pluck('name')
                    ->values();

                return [
                    'id' => $conversation->id,
                    'subject' => $conversation->subject ?: '(No subject)',
                    'other_participants' => $others->take(2)->implode(', '),
                    'updated_human' => $conversation->updated_at?->diffForHumans() ?? 'recently',
                    'unread_count' => $conversation->unreadCountFor($user),
                ];
            });

        $pendingRoleRequests = RoleChangeRequest::query()
            ->with(['target:id,name', 'department:id,name'])
            ->pending()
            ->when($managedDepartmentIds !== null, fn ($query) => $query->whereIn('department_id', $managedDepartmentIds))
            ->orderByDesc('created_at')
            ->take(4)
            ->get()
            ->map(fn (RoleChangeRequest $request) => [
                'id' => $request->id,
                'target_name' => $request->target?->name ?? 'Unknown user',
                'requested_role' => $request->requestedRoleLabel(),
                'department_name' => $request->department?->name,
                'created_human' => $request->created_at?->diffForHumans() ?? 'recently',
            ]);

        return [
            'department_id' => $departmentId,
            'breached_tickets' => $breachedTickets,
            'waiting_on_employees' => $waitingOnEmployees,
            'unread_conversations' => $unreadConversations,
            'pending_role_requests' => $pendingRoleRequests,
            'breached_count' => $breachedTickets->count(),
            'waiting_count' => $waitingOnEmployees->count(),
            'unread_count' => $unreadConversations->sum('unread_count'),
            'pending_request_count' => $pendingRoleRequests->count(),
        ];
    }
}
