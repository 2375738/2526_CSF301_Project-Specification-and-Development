<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConversationStoreRequest;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filterType = $request->query('type');

        if ($filterType && ! in_array($filterType, ['direct', 'department', 'announcement'], true)) {
            $filterType = null;
        }

        $conversations = Conversation::query()
            ->forUser($user)
            ->with([
                'participants:id,name,role',
                'messages' => fn ($query) => $query->latest()->with('sender:id,name,role')->limit(1),
                'department:id,name',
            ])
            ->when($filterType, fn ($query) => $query->where('type', $filterType))
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $managedDepartmentOptions = collect();

        if ($user->hasRole('hr', 'admin', 'ops_manager')) {
            $managedDepartmentOptions = Department::orderBy('name')->pluck('name', 'id');
        } elseif ($user->isManager()) {
            $managedDepartmentOptions = $user->managedDepartments()->orderBy('departments.name')->pluck('departments.name', 'departments.id');
        }

        $recipientOptions = User::query()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('messages.index', [
            'conversations' => $conversations,
            'managedDepartmentOptions' => $managedDepartmentOptions,
            'recipientOptions' => $recipientOptions,
            'activeType' => $filterType,
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $conversation->load([
            'participants:id,name,role',
            'messages.sender:id,name,role',
            'department:id,name',
        ]);

        $this->authorize('view', $conversation);

        $conversation->markReadFor($request->user());

        return view('messages.show', [
            'conversation' => $conversation,
        ]);
    }

    public function updateLock(Request $request, Conversation $conversation, AuditLogger $auditLogger): RedirectResponse
    {
        $conversation->loadMissing('participants');

        $this->authorize('lock', $conversation);

        $lock = $request->boolean('lock');

        $conversation->forceFill(['is_locked' => $lock])->save();

        $auditLogger->log($lock ? 'conversation.locked' : 'conversation.unlocked', $conversation, [
            'conversation_id' => $conversation->id,
            'locked' => $lock,
        ], $request->user());

        return Redirect::route('messages.show', $conversation)
            ->with('status', $lock ? 'Conversation locked.' : 'Conversation unlocked.');
    }

    public function store(ConversationStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Conversation::class);

        $user = $request->user();
        $data = $request->validated();

        $type = $data['type'] ?? 'direct';
        $canBroadcast = $user->hasRole('manager', 'ops_manager', 'hr', 'admin');

        if (! $canBroadcast) {
            $type = 'direct';
        }
        $subject = $data['subject'] ?? null;
        $body = $data['body'];

        $participantIds = collect();
        $department = null;

        if ($type === 'department') {
            $department = Department::findOrFail($data['department_id'] ?? 0);

            $allowed = $user->hasRole('hr', 'admin', 'ops_manager') ||
                $user->managedDepartments()->where('departments.id', $department->id)->exists();

            abort_unless($allowed, 403);

            $participantIds = $department->members()->pluck('users.id');
        } else {
            $recipientIds = collect($data['recipients'] ?? [])
                ->filter(fn ($id) => $id !== $user->id)
                ->unique();

            if ($recipientIds->isEmpty()) {
                return Redirect::back()
                    ->withErrors(['recipients' => 'Select at least one recipient.'])
                    ->withInput();
            }

            $participantIds = $recipientIds;
        }

        $participantIds = $participantIds->push($user->id)->unique();

        $conversation = Conversation::create([
            'subject' => $subject,
            'type' => $type,
            'creator_id' => $user->id,
            'department_id' => $department?->id,
            'is_locked' => false,
        ]);

        $syncData = $participantIds->mapWithKeys(fn ($id) => [
            $id => ['role' => 'member', 'last_read_at' => null],
        ])->toArray();

        $conversation->participants()->sync($syncData);
        $conversation->participants()->updateExistingPivot($user->id, [
            'role' => 'owner',
            'last_read_at' => now(),
        ]);

        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $body,
            'is_system' => false,
        ]);

        $conversation->touch();

        return Redirect::route('messages.show', $conversation)
            ->with('status', 'Conversation started.');
    }
}
