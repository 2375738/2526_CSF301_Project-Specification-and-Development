<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConversationStoreRequest;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\ManagerRelationship;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $context = $this->buildInboxContext($user, $request);

        return view('messages.index', array_merge($context, [
            'selectedConversation' => null,
        ]));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $conversation->load([
            'participants:id,name,role',
            'messages.sender:id,name,role',
            'messages.attachments',
            'department:id,name',
        ]);

        $this->authorize('view', $conversation);

        $conversation->markReadFor($request->user());

        $conversation->load([
            'participants:id,name,role',
            'messages.sender:id,name,role',
            'messages.attachments',
            'department:id,name',
        ]);

        $selectedConversation = $this->decorateSelectedConversation($conversation, $request->user());
        $context = $this->buildInboxContext($request->user(), $request);

        return view('messages.show', array_merge($context, [
            'selectedConversation' => $selectedConversation,
            'conversation' => $selectedConversation,
        ]));
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
        $shortcut = $data['shortcut'] ?? null;

        if (! $canBroadcast) {
            $type = 'direct';
        }
        $subject = $data['subject'] ?? null;
        $body = $data['body'];

        $participantIds = collect();
        $department = null;

        if ($shortcut) {
            $recipient = $this->resolveShortcutRecipient($user, $shortcut);

            if (! $recipient) {
                return Redirect::back()
                    ->withErrors(['shortcut' => 'The selected routed contact is not available right now.'])
                    ->withInput();
            }

            $type = 'direct';
            $participantIds = collect([$recipient->id]);
            $subject = $subject ?: $this->defaultShortcutSubject($shortcut, $recipient);
        } elseif ($type === 'department') {
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

        $conversation = null;

        if ($type === 'direct' && $participantIds->count() === 2) {
            $conversation = $this->findReusableDirectConversation($participantIds);
        }

        if (! $conversation) {
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
        } else {
            $conversation->participants()->updateExistingPivot($user->id, [
                'last_read_at' => now(),
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $this->normaliseBody($body),
            'is_system' => false,
        ]);

        $this->persistAttachments($request, $message);

        $conversation->touch();

        return Redirect::route('messages.show', $conversation)
            ->with('status', $shortcut ? 'Message routed.' : 'Conversation started.');
    }

    protected function buildShortcutOptions(User $user): Collection
    {
        return collect([
            [
                'key' => 'my_manager',
                'label' => 'Message My Manager',
                'description' => 'Send a direct question or blocker update to your reporting manager.',
                'recipient' => $this->resolveManagerShortcutRecipient($user),
            ],
            [
                'key' => 'support_team',
                'label' => 'Ask Support',
                'description' => 'Contact a support contact for site systems, facilities, or operational help.',
                'recipient' => $this->resolveSupportShortcutRecipient($user),
            ],
            [
                'key' => 'hr_team',
                'label' => 'Escalate to HR',
                'description' => 'Reach HR directly for people, policy, or attendance questions.',
                'recipient' => $this->resolveHrShortcutRecipient($user),
            ],
        ])->filter(fn (array $shortcut) => $shortcut['recipient'] instanceof User)
            ->map(function (array $shortcut): array {
                /** @var User $recipient */
                $recipient = $shortcut['recipient'];

                return [
                    'key' => $shortcut['key'],
                    'label' => $shortcut['label'],
                    'description' => $shortcut['description'],
                    'recipient_id' => $recipient->id,
                    'recipient_name' => $recipient->name,
                    'recipient_role' => $recipient->role?->value ?? $recipient->role,
                ];
            })
            ->values();
    }

    protected function resolveShortcutRecipient(User $user, string $shortcut): ?User
    {
        $recipient = match ($shortcut) {
            'my_manager' => $this->resolveManagerShortcutRecipient($user),
            'support_team' => $this->resolveSupportShortcutRecipient($user),
            'hr_team' => $this->resolveHrShortcutRecipient($user),
            default => null,
        };

        if (! $recipient || $recipient->id === $user->id) {
            return null;
        }

        return $recipient;
    }

    protected function resolveManagerShortcutRecipient(User $user): ?User
    {
        return ManagerRelationship::query()
            ->where('manager_id', $user->id)
            ->with('reportsTo:id,name,role')
            ->first()
            ?->reportsTo;
    }

    protected function resolveSupportShortcutRecipient(User $user): ?User
    {
        $supportDepartment = Department::query()
            ->whereRaw('LOWER(name) = ?', ['support'])
            ->first();

        if ($supportDepartment) {
            $supportUser = $supportDepartment->members()
                ->whereIn('users.role', ['manager', 'ops_manager', 'hr', 'admin'])
                ->orderBy('users.name')
                ->first(['users.id', 'users.name', 'users.role']);

            if ($supportUser && $supportUser->id !== $user->id) {
                return $supportUser;
            }
        }

        return User::query()
            ->where('id', '!=', $user->id)
            ->whereIn('role', ['ops_manager', 'admin', 'manager'])
            ->orderBy('name')
            ->first(['id', 'name', 'role']);
    }

    protected function resolveHrShortcutRecipient(User $user): ?User
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->whereIn('role', ['hr', 'admin'])
            ->orderByRaw("case role when 'hr' then 0 when 'admin' then 1 else 2 end")
            ->orderBy('name')
            ->first(['id', 'name', 'role']);
    }

    protected function defaultShortcutSubject(string $shortcut, User $recipient): string
    {
        return match ($shortcut) {
            'my_manager' => 'Question for manager',
            'support_team' => 'Support request',
            'hr_team' => 'HR question',
            default => 'Direct message',
        };
    }

    protected function findReusableDirectConversation(Collection $participantIds): ?Conversation
    {
        $ids = $participantIds->values()->all();

        $conversation = Conversation::query()
            ->where('type', 'direct')
            ->where('is_locked', false)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $ids[0]))
            ->whereHas('participants', fn ($query) => $query->where('users.id', $ids[1]))
            ->whereDoesntHave('participants', fn ($query) => $query->whereNotIn('users.id', $ids))
            ->orderByDesc('updated_at')
            ->first();

        return $conversation;
    }

    protected function persistAttachments(ConversationStoreRequest $request, Message $message): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $message->attachments()->create([
                'user_id' => $request->user()->id,
                'disk' => 'attachments',
                'path' => $file->storeAs(
                    'messages/' . $message->conversation_id . '/' . $message->id,
                    Str::uuid() . '.' . $file->getClientOriginalExtension(),
                    'attachments'
                ),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    protected function normaliseBody(?string $body): ?string
    {
        $body = trim((string) $body);

        return $body === '' ? null : $body;
    }

    protected function buildInboxContext(User $user, Request $request): array
    {
        $filterType = $request->query('type');
        $search = trim((string) $request->query('q', ''));

        if ($filterType && ! in_array($filterType, ['direct', 'department', 'announcement'], true)) {
            $filterType = null;
        }

        $baseQuery = $this->conversationInboxQuery($user, $search);

        /** @var LengthAwarePaginator $conversations */
        $conversations = (clone $baseQuery)
            ->when($filterType, fn ($query) => $query->where('type', $filterType))
            ->orderByDesc('updated_at')
            ->paginate(14)
            ->withQueryString();

        $conversations->setCollection(
            $conversations->getCollection()->map(fn (Conversation $conversation) => $this->decorateInboxConversation($conversation, $user))
        );

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

        return [
            'conversations' => $conversations,
            'unreadConversationCount' => $unreadConversationCount,
            'managedDepartmentOptions' => $managedDepartmentOptions,
            'recipientOptions' => $recipientOptions,
            'shortcutOptions' => $this->buildShortcutOptions($user),
            'activeType' => $filterType,
            'search' => $search,
        ];
    }

    protected function conversationInboxQuery(User $user, string $search = ''): Builder
    {
        return Conversation::query()
            ->forUser($user)
            ->with([
                'participants:id,name,role',
                'messages' => fn ($query) => $query->latest()->with('sender:id,name,role')->limit(1),
                'department:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('subject', 'like', '%' . $search . '%')
                        ->orWhereHas('participants', fn ($participants) => $participants->where('users.name', 'like', '%' . $search . '%'))
                        ->orWhereHas('messages', fn ($messages) => $messages->where('body', 'like', '%' . $search . '%'));
                });
            });
    }

    protected function decorateInboxConversation(Conversation $conversation, User $user): Conversation
    {
        $latestMessage = $conversation->messages->sortByDesc('created_at')->first();

        $conversation->latest_message = $latestMessage;
        $conversation->unread_count = $conversation->unreadCountFor($user);

        return $conversation;
    }

    protected function decorateSelectedConversation(Conversation $conversation, User $user): Conversation
    {
        $conversation->latest_message = $conversation->messages->sortByDesc('created_at')->first();
        $conversation->unread_count = 0;

        return $conversation;
    }
}
