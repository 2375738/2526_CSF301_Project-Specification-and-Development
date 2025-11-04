<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\SavedAnalyticsView;
use App\Models\Ticket;
use App\Services\AnalyticsExportService;
use App\Services\DepartmentAnalyticsService;
use App\Services\SLAService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;

class AnalyticsController extends Controller
{
    public function index(Request $request, SLAService $slaService, DepartmentAnalyticsService $deptAnalytics)
    {
        $openByPriority = Ticket::query()
            ->select('priority', DB::raw('count(*) as total'))
            ->whereIn('status', array_map(fn ($status) => $status->value, TicketStatus::open()))
            ->groupBy('priority')
            ->get()
            ->sortBy(fn ($row) => array_search($row->priority, ['critical', 'high', 'medium', 'low']))
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

        $savedViews = $request->user()?->savedAnalyticsViews()->orderBy('name')->get() ?? collect();
        $activeSavedView = null;

        $trendDays = max(3, min(30, (int) $request->integer('days', 7)));
        $departmentId = $request->filled('department_id') ? (int) $request->input('department_id') : null;

        if ($request->filled('saved_view_id')) {
            $activeSavedView = $savedViews->firstWhere('id', (int) $request->input('saved_view_id'));
            if ($activeSavedView) {
                $trendDays = $activeSavedView->days;
                $departmentId = $activeSavedView->department_id;
            }
        }

        $trendStart = Carbon::now()->subDays($trendDays - 1)->startOfDay();

        for ($i = 0; $i < $trendDays; $i++) {
            $date = Carbon::now()->subDays($i);
            $exists = DepartmentMetric::whereDate('metric_date', $date->toDateString())->exists();

            if (! $exists) {
                $deptAnalytics->recalculateForDate($date);
            }
        }

        $trendMetrics = DepartmentMetric::query()
            ->where('metric_date', '>=', $trendStart->toDateString())
            ->where('department_id', $departmentId)
            ->orderBy('metric_date')
            ->get()
            ->map(fn (DepartmentMetric $metric) => [
                'date' => $metric->metric_date->format('M j'),
                'open' => $metric->open_tickets,
                'breaches' => $metric->sla_breaches,
                'messages' => $metric->messages_sent,
            ]);

        $departmentOptions = Department::orderBy('name')->pluck('name', 'id');

        return view('admin.analytics', [
            'openByPriority' => $openByPriority,
            'breachesLastWeek' => $breachesLastWeek,
            'firstResponseAvg' => $firstResponseMinutes,
            'resolutionAvg' => $resolutionMinutes,
            'topCategories' => $topCategories,
            'recentActivity' => $recentActivity,
            'trendMetrics' => $trendMetrics,
            'departmentOptions' => $departmentOptions,
            'selectedDepartment' => $departmentId,
            'trendDays' => $trendDays,
            'savedViews' => $savedViews,
            'activeSavedView' => $activeSavedView,
        ]);
    }

    public function storeView(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'days' => ['required', Rule::in([7, 14, 30])],
        ]);

        /** @var SavedAnalyticsView $view */
        $view = $request->user()
            ->savedAnalyticsViews()
            ->updateOrCreate([
                'name' => $data['name'],
            ], [
                'department_id' => $data['department_id'] ?? null,
                'days' => $data['days'],
            ]);

        return redirect()
            ->route('analytics.index', [
                'saved_view_id' => $view->id,
            ])
            ->with('status', 'Analytics view saved.');
    }

    public function export(AnalyticsExportService $exporter): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="site-analytics.csv"',
        ];

        $exportData = $exporter->generateTicketExport();

        $callback = function () use ($exporter, $exportData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $exportData['headers']);
            foreach ($exportData['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, 'site-analytics.csv', $headers);
    }
}
