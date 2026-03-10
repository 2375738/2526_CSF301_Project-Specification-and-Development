<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\Department;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SLAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportTicketController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Ticket::class);

        $user = $request->user();
        $categories = Category::orderBy('order')->get();
        $presetKey = (string) $request->query('preset', old('preset', ''));
        $quickPresets = $this->buildQuickPresets($categories);
        $selectedPreset = $quickPresets->firstWhere('key', $presetKey);

        $title = old('title', $request->query('title', $selectedPreset['title'] ?? null));
        $description = old('description', $request->query('description', $selectedPreset['description_template'] ?? null));
        $location = old('location', $request->query('location', $selectedPreset['location'] ?? null));
        $categoryId = (int) old('category_id', $request->query('category_id', $selectedPreset['category_id'] ?? null));

        $similarTickets = $this->similarTickets(
            $user,
            $title,
            $categoryId ?: null
        );

        $canActOnBehalf = $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
        $employeeOptions = collect();
        $departmentOptions = collect();

        if ($canActOnBehalf) {
            $departmentQuery = Department::query()->orderBy('name');
            $employeeQuery = User::query()->orderBy('name');

            if ($user->hasRole('hr', 'admin', 'ops_manager')) {
                // no restrictions
            } else {
                $managedIds = $user->managedDepartments()->pluck('departments.id');
                $departmentQuery->whereIn('id', $managedIds);
                $employeeQuery->whereHas('departments', fn ($q) => $q->whereIn('departments.id', $managedIds));
            }

            $departmentOptions = $departmentQuery->get(['id', 'name']);
            $employeeOptions = $employeeQuery->get(['id', 'name']);
        }

        return view('tickets.report', [
            'categories' => $categories,
            'similarTickets' => $similarTickets,
            'canActOnBehalf' => $canActOnBehalf,
            'employeeOptions' => $employeeOptions,
            'departmentOptions' => $departmentOptions,
            'quickPresets' => $quickPresets,
            'selectedPreset' => $selectedPreset,
            'prefillTitle' => $title,
            'prefillDescription' => $description,
            'prefillLocation' => $location,
            'prefillCategoryId' => $categoryId,
        ]);
    }

    public function store(
        StoreTicketRequest $request,
        SLAService $slaService,
        NotificationService $notifier
    ): RedirectResponse {
        $user = $request->user();

        $canActOnBehalf = $user->hasRole('manager', 'ops_manager', 'hr', 'admin');
        $createdFor = null;

        if ($canActOnBehalf && $request->filled('created_for_id')) {
            $createdFor = User::findOrFail($request->integer('created_for_id'));

            if (! $user->hasRole('hr', 'admin', 'ops_manager')) {
                $managedIds = $user->managedDepartments()->pluck('departments.id');
                abort_unless($createdFor->departments()->whereIn('departments.id', $managedIds)->exists(), 403);
            }
        } elseif (! $canActOnBehalf) {
            $createdFor = $user;
        }

        if (! $createdFor) {
            $createdFor = $user;
        }

        $departmentId = null;

        if ($request->filled('department_id')) {
            $department = Department::findOrFail($request->integer('department_id'));

            if (! $user->hasRole('hr', 'admin', 'ops_manager')) {
                $managedIds = $user->managedDepartments()->pluck('departments.id');
                abort_unless($managedIds->contains($department->id), 403);
            }

            $departmentId = $department->id;
        }

        if (! $departmentId) {
            $departmentId = $createdFor?->primary_department_id ?? $user->primary_department_id;
        }

        $ticket = Ticket::create([
            'requester_id' => $user->id,
            'created_for_id' => $createdFor?->id,
            'department_id' => $departmentId,
            'category_id' => $request->integer('category_id'),
            'priority' => TicketPriority::Medium->value,
            'status' => TicketStatus::New->value,
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
            'location' => $request->filled('location') ? trim((string) $request->input('location')) : null,
        ]);

        $ticket->statusChanges()->create([
            'user_id' => $user->id,
            'from_status' => null,
            'to_status' => TicketStatus::New->value,
            'reason' => 'Ticket created by requester',
        ]);

        if ($file = $request->file('attachment')) {
            $ticket->attachments()->create([
                'user_id' => $user->id,
                'disk' => 'attachments',
                'path' => $file->storeAs(
                    'tickets/' . $ticket->id,
                    Str::uuid() . '.' . $file->getClientOriginalExtension(),
                    'attachments'
                ),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $slaEvaluation = $slaService->evaluate($ticket);

        $ticket->forceFill([
            'sla_first_response_breached' => $slaEvaluation['first_response_breached'],
            'sla_resolution_breached' => $slaEvaluation['resolution_breached'],
        ])->save();

        $notifier->ticketCreated($ticket);

        $targets = $slaService->targets($ticket->priority);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with(
                'status',
                "Ticket #{$ticket->id} created. Target first response: {$targets['first_response_minutes']} minutes."
            );
    }

    public function link(Request $request, NotificationService $notifier): RedirectResponse
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $ticket = Ticket::findOrFail($data['ticket_id']);

        $this->authorize('comment', $ticket);

        $commentBody = trim($data['message'] ?? '') ?: 'Marked as related issue by requester.';

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $commentBody,
            'is_private' => false,
        ]);

        $notifier->commentAdded($ticket, $comment->author, false);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Linked to ticket #{$ticket->id}. We'll keep you posted there.");
    }

    protected function similarTickets(?\App\Models\User $user, ?string $title, ?int $categoryId): Collection
    {
        if (! $title || mb_strlen($title) < 3) {
            return collect();
        }

        $keywords = collect(preg_split('/\s+/', Str::lower($title)))
            ->filter(fn ($word) => mb_strlen($word) >= 3)
            ->take(3);

        if ($keywords->isEmpty()) {
            return collect();
        }

        $query = Ticket::query()
            ->open()
            ->whereNull('duplicate_of_id')
            ->whereHas('category', fn ($q) => $q->where('is_sensitive', false))
            ->with(['requester'])
            ->orderByDesc('created_at')
            ->limit(5);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $query->where(function ($q) use ($keywords, $user) {
            foreach ($keywords as $word) {
                $q->orWhere('title', 'like', '%' . $word . '%');
            }

            if ($user && ! $user->hasRole('manager', 'ops_manager', 'hr', 'admin')) {
                $q->orWhere('requester_id', $user->id);
            }
        });

        return $query->get();
    }

    protected function buildQuickPresets(Collection $categories): Collection
    {
        $categoryLookup = $categories->keyBy(fn (Category $category) => Str::lower($category->name));

        $resolveCategoryId = function (array $names) use ($categoryLookup): ?int {
            foreach ($names as $name) {
                $category = $categoryLookup->get(Str::lower($name));
                if ($category) {
                    return $category->id;
                }
            }

            return null;
        };

        return collect([
            [
                'key' => 'scanner',
                'label' => 'Scanner issue',
                'description' => 'Battery, login, pairing, or connection problem.',
                'title' => 'Scanner issue at station',
                'description_template' => 'Scanner problem observed. Device is not working as expected and is blocking task progress.',
                'location' => 'Station / area',
                'category_id' => $resolveCategoryId(['IT Support', 'Operations']),
            ],
            [
                'key' => 'safety',
                'label' => 'Safety concern',
                'description' => 'Hazard, spill, blocked path, or damaged equipment.',
                'title' => 'Safety concern in work area',
                'description_template' => 'Safety concern observed. Immediate risk and impact need review.',
                'location' => 'Affected area',
                'category_id' => $resolveCategoryId(['Safety', 'Operations']),
            ],
            [
                'key' => 'facilities',
                'label' => 'Facilities issue',
                'description' => 'Door, lighting, printer, workstation, or canteen issue.',
                'title' => 'Facilities issue affecting work area',
                'description_template' => 'Facilities issue observed. The problem is affecting normal work or access.',
                'location' => 'Affected area',
                'category_id' => $resolveCategoryId(['Facilities', 'Operations']),
            ],
            [
                'key' => 'transport',
                'label' => 'Transport problem',
                'description' => 'Bus, shuttle, parking, or route disruption.',
                'title' => 'Transport problem for shift travel',
                'description_template' => 'Transport issue observed. Include route, stop, and impact on arrival or departure.',
                'location' => 'Bus stop / route',
                'category_id' => $resolveCategoryId(['Transport']),
            ],
        ])->map(function (array $preset): array {
            return [
                'key' => $preset['key'],
                'label' => $preset['label'],
                'description' => $preset['description'],
                'title' => $preset['title'],
                'description_template' => $preset['description_template'],
                'location' => $preset['location'],
                'category_id' => $preset['category_id'],
            ];
        })->filter(fn (array $preset) => $preset['category_id'] !== null)->values();
    }
}
