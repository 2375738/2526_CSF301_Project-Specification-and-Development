<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Category;
use App\Services\PerformanceService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PerformanceService $performanceService)
    {
        $user = $request->user();

        $announcements = Announcement::query()
            ->active()
            ->ordered()
            ->take(6)
            ->get();

        $categories = Category::query()
            ->with(['links' => fn ($query) => $query->active()->orderBy('order')])
            ->when(
                ! $user || ! $user->hasRole('manager', 'admin'),
                fn ($query) => $query->public()
            )
            ->orderBy('order')
            ->get();

        $snapshots = collect();
        $riskFlag = false;

        if ($user && $user->isEmployee()) {
            $snapshots = $user->performanceSnapshots()->recent()->orderByDesc('week_start')->get();
            $riskFlag = $performanceService->riskFlag($snapshots);
        }

        return view('dashboard.index', [
            'announcements' => $announcements,
            'categories' => $categories,
            'snapshots' => $snapshots,
            'riskFlag' => $riskFlag,
        ]);
    }
}
