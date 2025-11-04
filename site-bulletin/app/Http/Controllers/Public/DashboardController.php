<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\DepartmentMetric;
use App\Models\Ticket;
use App\Services\DepartmentAnalyticsService;
use App\Services\PerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        PerformanceService $performanceService,
        DepartmentAnalyticsService $departmentAnalytics
    )
    {
        $user = $request->user();

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
            ->visibleTo($user)
            ->orderBy('order')
            ->get();

        $snapshots = collect();
        $riskFlag = false;
        $messagePreview = collect();
        $unreadConversationCount = 0;
        $governanceLogs = collect();
        $departmentMetricTrend = collect();

        if ($user && $user->isEmployee()) {
            $snapshots = $user->performanceSnapshots()->recent()->orderByDesc('week_start')->get();
            $riskFlag = $performanceService->riskFlag($snapshots);
        }

        if ($user) {
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

            if ($user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
                $managedIds = $user->managedDepartments()->pluck('departments.id');

                $governanceLogs = AuditLog::query()
                    ->with('actor:id,name')
                    ->when($user->hasRole('manager', 'ops_manager') && $managedIds->isNotEmpty(), function ($query) use ($managedIds, $user) {
                        $query->where(function ($inner) use ($managedIds, $user) {
                            $inner->where('actor_id', $user->id)
                                ->orWhereHasMorph('auditable', [Ticket::class], fn ($ticketQuery) => $ticketQuery
                                    ->whereIn('department_id', $managedIds));
                        });
                    })
                    ->latest('occurred_at')
                    ->take(5)
                    ->get();
            }

            if ($user->hasRole('manager', 'ops_manager')) {
                if (! DepartmentMetric::query()->whereDate('metric_date', Carbon::today()->toDateString())->exists()) {
                    $departmentAnalytics->recalculateForDate(Carbon::now());
                }

                $primaryDepartmentId = $user->primary_department_id;

                $departmentMetricTrend = DepartmentMetric::query()
                    ->where('department_id', $primaryDepartmentId)
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
            }
        }

        return view('dashboard.index', [
            'announcements' => $announcements,
            'categories' => $categories,
            'snapshots' => $snapshots,
            'riskFlag' => $riskFlag,
            'messagePreview' => $messagePreview,
            'unreadConversationCount' => $unreadConversationCount,
            'governanceLogs' => $governanceLogs,
            'departmentMetricTrend' => $departmentMetricTrend,
        ]);
    }
}
