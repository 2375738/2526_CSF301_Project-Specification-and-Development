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
use App\Models\TicketApproval;
use App\Services\NotificationService;
use App\Services\SLAService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
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
        $templateKey = (string) $request->query('template', $request->query('preset', old('template', old('preset', ''))));
        $ticketTemplates = $this->buildTicketTemplates($categories, $user);
        $selectedTemplate = $ticketTemplates->firstWhere('key', $templateKey);

        $title = old('title', $request->query('title', $selectedTemplate['title'] ?? null));
        $description = old('description', $request->query('description', $selectedTemplate['description_template'] ?? null));
        $location = old('location', $request->query('location', $selectedTemplate['location_label'] ?? null));
        $categoryId = (int) old('category_id', $request->query('category_id', $selectedTemplate['category_id'] ?? null));

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
            'ticketTemplates' => $ticketTemplates,
            'selectedTemplate' => $selectedTemplate,
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
        $templateKey = (string) $request->input('template', $request->input('preset', ''));
        $template = $this->buildTicketTemplates(Category::orderBy('order')->get(), $user)->firstWhere('key', $templateKey);

        if ($templateKey !== '' && ! $template) {
            return Redirect::back()
                ->withErrors(['template' => 'The selected ticket template is not available for your role.'])
                ->withInput();
        }

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

        $detailAnswers = collect($request->input('detail_answers', []))
            ->map(fn ($value) => is_string($value) ? trim($value) : null)
            ->values();

        $detailPayload = $template
            ? $this->buildStructuredDetails($template, $detailAnswers)
            : [];

        $ticket = Ticket::create([
            'requester_id' => $user->id,
            'created_for_id' => $createdFor?->id,
            'department_id' => $departmentId,
            'template_key' => $template['key'] ?? null,
            'category_id' => $request->integer('category_id'),
            'priority' => $template['default_priority'] ?? TicketPriority::Medium->value,
            'status' => TicketStatus::New->value,
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
            'details_json' => $detailPayload,
            'location' => $request->filled('location') ? trim((string) $request->input('location')) : null,
        ]);

        $ticket->statusChanges()->create([
            'user_id' => $user->id,
            'from_status' => null,
            'to_status' => TicketStatus::New->value,
            'reason' => 'Ticket created by requester',
        ]);

        foreach ($this->approvalStepsForTemplate($template) as $index => $step) {
            $ticket->approvals()->create([
                'step_order' => $index + 1,
                'step_key' => $step['step_key'],
                'approver_role' => $step['approver_role'],
                'status' => $index === 0 ? TicketApproval::STATUS_PENDING : TicketApproval::STATUS_QUEUED,
                'public_note' => $step['requester_status'] ?? null,
            ]);
        }

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

    protected function buildTicketTemplates(Collection $categories, User $user): Collection
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

        return collect(config('ticket_templates', []))
            ->map(function (array $template, string $key) use ($resolveCategoryId, $user): ?array {
                if (! in_array($user->role?->value ?? $user->role, $template['requester_roles'] ?? [], true)) {
                    return null;
                }

                $categoryId = $resolveCategoryId($template['category_names'] ?? []);

                if (! $categoryId) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $template['label'],
                    'description' => $template['description'],
                    'title' => $template['title'],
                    'description_template' => $template['description_template'],
                    'location_label' => $template['location_label'],
                    'category_id' => $categoryId,
                    'default_priority' => $template['default_priority'] ?? TicketPriority::Medium->value,
                    'detail_prompts' => $template['detail_prompts'] ?? [],
                    'evidence_hint' => $template['evidence_hint'] ?? null,
                    'requester_roles' => $template['requester_roles'] ?? [],
                    'approval' => $template['approval'] ?? null,
                    'approval_steps' => $template['approval_steps'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->map(function (array $template): array {
            return [
                'key' => $template['key'],
                'label' => $template['label'],
                'description' => $template['description'],
                'title' => $template['title'],
                'description_template' => $template['description_template'],
                'location_label' => $template['location_label'],
                'category_id' => $template['category_id'],
                'default_priority' => $template['default_priority'],
                'detail_prompts' => $template['detail_prompts'],
                'evidence_hint' => $template['evidence_hint'],
                'approval' => $template['approval'],
                'approval_steps' => $template['approval_steps'],
            ];
        });
    }

    protected function buildStructuredDetails(array $template, Collection $detailAnswers): array
    {
        return collect($template['detail_prompts'] ?? [])
            ->values()
            ->map(function (string $prompt, int $index) use ($detailAnswers): ?array {
                $answer = $detailAnswers->get($index);

                if (! is_string($answer) || $answer === '') {
                    return null;
                }

                return [
                    'label' => $prompt,
                    'value' => $answer,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function approvalStepsForTemplate(?array $template): Collection
    {
        if (! $template) {
            return collect();
        }

        if (! empty($template['approval_steps'])) {
            return collect($template['approval_steps']);
        }

        if (! empty($template['approval'])) {
            return collect([$template['approval']]);
        }

        return collect();
    }
}
