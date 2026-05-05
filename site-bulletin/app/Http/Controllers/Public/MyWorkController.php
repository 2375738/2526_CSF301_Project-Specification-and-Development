<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Conversation;
use App\Services\DepartmentAnalyticsService;
use App\Services\DemoOperationsSimulationService;
use App\Services\PerformanceService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MyWorkController extends DashboardController
{
    public function __invoke(
        Request $request,
        PerformanceService $performanceService,
        DepartmentAnalyticsService $departmentAnalytics,
        DemoOperationsSimulationService $demoOperationsSimulation
    ): View|RedirectResponse {
        $user = $request->user();

        if (! $user || ! $user->isEmployee()) {
            return redirect()->route('home');
        }

        $demoOperationsSimulation->ensureFreshSamples();

        $snapshots = $user->performanceSnapshots()->recent()->orderByDesc('week_start')->get();
        $riskFlag = $performanceService->riskFlag($snapshots);
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

        return view('my-work.index', [
            'snapshots' => $snapshots,
            'riskFlag' => $riskFlag,
            'employeeWorkToday' => $employeeWorkToday,
            'employeeOverview' => $employeeOverview,
            'employeeScale' => $employeeScale,
        ]);
    }
}
