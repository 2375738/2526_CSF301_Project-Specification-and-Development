<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Services\RoleScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(protected RoleScopeService $roleScope)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:newest,oldest,priority,unread'],
            'filter' => ['nullable', 'in:all,unread,high-priority,my-department'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $sort = $validated['sort'] ?? 'newest';
        $filter = $validated['filter'] ?? 'all';

        $query = Announcement::query()
            ->with(['author:id,name', 'department:id,name'])
            ->active()
            ->visibleTo($user)
            ->addSelect([
                'user_acknowledgement' => DB::table('announcement_reads')
                    ->select('acknowledgement')
                    ->whereColumn('announcement_reads.announcement_id', 'announcements.id')
                    ->where('announcement_reads.user_id', $user->id)
                    ->limit(1),
            ])
            ->withExists([
                'readers as is_read' => fn (Builder $builder) => $builder->where('users.id', $user->id),
            ]);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%')
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($filter === 'unread') {
            $query->whereDoesntHave('readers', fn (Builder $builder) => $builder->where('users.id', $user->id));
        }

        if ($filter === 'high-priority') {
            $query->whereIn('priority', ['high', 'urgent']);
        }

        if ($filter === 'my-department') {
            $departmentIds = $user->departmentIds();
            $query->where(function (Builder $builder) use ($departmentIds) {
                $builder->where('audience', 'all');

                if ($departmentIds->isNotEmpty()) {
                    $builder->orWhere(function (Builder $departmentQuery) use ($departmentIds) {
                        $departmentQuery->where('audience', 'department')
                            ->whereIn('department_id', $departmentIds);
                    });
                }
            });
        }

        $this->applySort($query, $sort, $user->id);

        $announcements = $query
            ->paginate(12)
            ->withQueryString();

        $stats = $this->buildStats($user->id, $user->departmentIds(), $user);
        $canCreate = $this->canCreateAnnouncement($user);
        $audienceOptions = $this->audienceOptions($user);
        $departmentOptions = $this->departmentOptions($user);

        return view('announcements.index', [
            'announcements' => $announcements,
            'stats' => $stats,
            'search' => $search,
            'sort' => $sort,
            'filter' => $filter,
            'canCreate' => $canCreate,
            'audienceOptions' => $audienceOptions,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canCreateAnnouncement($user), 403);

        $audienceOptions = array_keys($this->audienceOptions($user));
        $departmentOptions = $this->departmentOptions($user);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'audience' => ['required', 'in:' . implode(',', $audienceOptions)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        if ($data['audience'] === 'department') {
            $departmentId = (int) ($data['department_id'] ?? 0);
            abort_unless($departmentOptions->has($departmentId), 403);
        } else {
            $data['department_id'] = null;
        }

        Announcement::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'priority' => $data['priority'],
            'audience' => $data['audience'],
            'department_id' => $data['department_id'] ?? null,
            'author_id' => $user->id,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
        ]);

        return redirect()
            ->route('announcements.index')
            ->with('status', 'Announcement created.');
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $user = $request->user();
        $this->abortUnlessVisible($announcement, $user);

        $announcement->load(['author:id,name', 'department:id,name']);
        $announcement->markReadFor($user);
        $receipt = $announcement->receiptFor($user);
        $acknowledgementSummary = $this->buildAcknowledgementSummary($announcement);

        $relatedAnnouncements = Announcement::query()
            ->with(['author:id,name', 'department:id,name'])
            ->active()
            ->visibleTo($user)
            ->whereKeyNot($announcement->id)
            ->ordered()
            ->take(5)
            ->get();

        return view('announcements.show', [
            'announcement' => $announcement,
            'receipt' => $receipt,
            'acknowledgementSummary' => $acknowledgementSummary,
            'relatedAnnouncements' => $relatedAnnouncements,
        ]);
    }

    public function markRead(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->abortUnlessVisible($announcement, $request->user());

        $announcement->markReadFor($request->user());

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();

        $announcementIds = Announcement::query()
            ->active()
            ->visibleTo($user)
            ->pluck('id');

        if ($announcementIds->isEmpty()) {
            return back();
        }

        $pivotData = $announcementIds->mapWithKeys(fn (int $announcementId) => [
            $announcementId => ['read_at' => now()],
        ])->toArray();

        $user->readAnnouncements()->syncWithoutDetaching($pivotData);

        return back();
    }

    public function acknowledge(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();
        $this->abortUnlessVisible($announcement, $user);

        $data = $request->validate([
            'acknowledgement' => ['required', 'in:' . implode(',', Announcement::acknowledgementOptions())],
        ]);

        $announcement->acknowledgeFor($user, $data['acknowledgement']);

        return back()->with('status', 'Acknowledgement saved.');
    }

    protected function applySort(Builder $query, string $sort, int $userId): void
    {
        if ($sort === 'oldest') {
            $query->orderBy('starts_at')->orderBy('created_at');
            return;
        }

        if ($sort === 'priority') {
            $query->orderByRaw(
                "case priority when 'urgent' then 0 when 'high' then 1 when 'medium' then 2 else 3 end"
            )->orderByDesc('created_at');
            return;
        }

        if ($sort === 'unread') {
            $query
                ->withExists([
                    'readers as sort_is_read' => fn (Builder $builder) => $builder->where('users.id', $userId),
                ])
                ->orderBy('sort_is_read')
                ->orderByDesc('starts_at')
                ->orderByDesc('created_at');
            return;
        }

        $query->ordered();
    }

    protected function buildStats(int $userId, $departmentIds, User $user): array
    {
        $base = Announcement::query()->active()->visibleTo($user);

        $total = (clone $base)->count();

        $unread = (clone $base)
            ->whereDoesntHave('readers', fn (Builder $builder) => $builder->where('users.id', $userId))
            ->count();

        $highPriority = (clone $base)
            ->whereIn('priority', ['high', 'urgent'])
            ->count();

        $myDepartment = (clone $base)
            ->where(function (Builder $builder) use ($departmentIds) {
                $builder->where('audience', 'all');

                if ($departmentIds->isNotEmpty()) {
                    $builder->orWhere(function (Builder $departmentQuery) use ($departmentIds) {
                        $departmentQuery->where('audience', 'department')
                            ->whereIn('department_id', $departmentIds);
                    });
                }
            })
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'high_priority' => $highPriority,
            'my_department' => $myDepartment,
        ];
    }

    protected function buildAcknowledgementSummary(Announcement $announcement): array
    {
        $rows = DB::table('announcement_reads')
            ->select('acknowledgement', DB::raw('count(*) as total'))
            ->where('announcement_id', $announcement->id)
            ->groupBy('acknowledgement')
            ->pluck('total', 'acknowledgement');

        $read = (int) DB::table('announcement_reads')
            ->where('announcement_id', $announcement->id)
            ->count();

        $understood = (int) ($rows[Announcement::ACKNOWLEDGEMENT_UNDERSTOOD] ?? 0);
        $needsClarification = (int) ($rows[Announcement::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION] ?? 0);

        return [
            'read' => $read,
            'understood' => $understood,
            'needs_clarification' => $needsClarification,
            'read_only' => max($read - $understood - $needsClarification, 0),
        ];
    }

    protected function abortUnlessVisible(Announcement $announcement, User $user): void
    {
        $visible = Announcement::query()
            ->active()
            ->visibleTo($user)
            ->whereKey($announcement->id)
            ->exists();

        abort_unless($visible, 404);
    }

    protected function canCreateAnnouncement(User $user): bool
    {
        return $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
    }

    protected function audienceOptions(User $user): array
    {
        if ($this->roleScope->canManageAllDepartments($user)) {
            return [
                'all' => 'All Departments',
                'department' => 'Specific Department',
                'managers' => 'Managers Only',
            ];
        }

        return [
            'department' => 'Specific Department',
        ];
    }

    protected function departmentOptions(User $user)
    {
        return $this->roleScope->departmentOptionsForManagement($user);
    }
}
