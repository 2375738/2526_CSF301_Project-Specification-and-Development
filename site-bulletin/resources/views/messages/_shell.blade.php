@php
  $filterOptions = [
    '' => 'All',
    'direct' => 'Direct',
    'department' => 'Department Broadcast',
    'announcement' => 'Announcements',
  ];

  $activeType = $activeType ?? null;
  $search = $search ?? '';
  $selectedConversation = $selectedConversation ?? null;
  $currentUser = auth()->user();

  $conversationTitle = function ($conversation) use ($currentUser) {
      if (! empty($conversation->subject)) {
          return $conversation->subject;
      }

      if ($conversation->type === 'direct') {
          $otherNames = $conversation->participants
              ->where('id', '!=', $currentUser->id)
              ->pluck('name')
              ->filter()
              ->values();

          if ($otherNames->isNotEmpty()) {
              return $otherNames->implode(', ');
          }
      }

      return match ($conversation->type) {
          'department' => 'Department Broadcast',
          'announcement' => 'Announcement Thread',
          default => 'Direct conversation',
      };
  };

  $conversationSubtitle = function ($conversation) use ($currentUser) {
      if ($conversation->type === 'direct') {
          $others = $conversation->participants
              ->where('id', '!=', $currentUser->id)
              ->map(fn ($participant) => $participant->name . ' · ' . ucfirst((string) ($participant->role?->value ?? $participant->role)))
              ->filter()
              ->values();

          return $others->isNotEmpty() ? $others->implode(', ') : 'Direct chat';
      }

      if ($conversation->type === 'department') {
          return trim(($conversation->department?->name ? $conversation->department->name . ' · ' : '') . 'Department broadcast');
      }

      return 'Announcement-linked thread';
  };

  $conversationInitials = function ($label) {
      return collect(preg_split('/\s+/', trim((string) $label)) ?: [])
          ->filter()
          ->take(2)
          ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
          ->implode('') ?: 'SB';
  };

  $typeBadgeClass = function ($type) {
      return match ($type) {
          'department' => 'bg-blue-100 text-blue-700',
          'announcement' => 'bg-amber-100 text-amber-700',
          default => 'bg-slate-100 text-slate-600',
      };
  };

  $isComposeState = ! $selectedConversation;
@endphp

<div class="space-y-5" x-data="{ composeOpen: false }">
  @if (session('status'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
      {{ session('status') }}
    </div>
  @endif

  <div class="grid gap-4 xl:grid-cols-[340px_minmax(0,1fr)]">
    <aside class="min-w-0 rounded-[24px] border border-slate-200 bg-white shadow-sm {{ $selectedConversation ? 'hidden xl:block' : '' }}">
      <div class="border-b border-slate-200 px-4 py-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Inbox</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">Recent Chats</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $unreadConversationCount ?? 0 }} unread conversation{{ ($unreadConversationCount ?? 0) === 1 ? '' : 's' }}.</p>
          </div>
          <button type="button" x-on:click="composeOpen = true" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900" aria-label="New chat">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M9.25 3a.75.75 0 00-1.5 0v5.25H2.5a.75.75 0 000 1.5h5.25V15a.75.75 0 001.5 0V9.75H14.5a.75.75 0 000-1.5H9.25V3z" />
            </svg>
          </button>
        </div>

        <form method="GET" action="{{ route('messages.index') }}" class="mt-4">
          @if ($activeType)
            <input type="hidden" name="type" value="{{ $activeType }}">
          @endif
          <label class="relative block">
            <span class="sr-only">Search messages</span>
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 014.352 8.864l3.642 3.642a.75.75 0 11-1.06 1.06l-3.642-3.642A5.5 5.5 0 118.5 3zm0 1.5a4 4 0 100 8 4 4 0 000-8z" clip-rule="evenodd" />
            </svg>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search or start a new chat" class="w-full rounded-xl border border-slate-200 bg-slate-100 py-2.5 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-500 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200">
          </label>
        </form>

        <nav class="mt-4 flex flex-wrap gap-2 text-sm">
          @foreach ($filterOptions as $value => $label)
            @php
              $isActive = ($activeType ?? '') === $value || ($value === '' && empty($activeType));
              $query = collect(request()->except(['page', 'type']))->when($value !== '', fn ($q) => $q->put('type', $value))->all();
            @endphp
            <a
              href="{{ route('messages.index', $query) }}"
              @if ($isActive) aria-current="page" @endif
              class="inline-flex items-center rounded-full border px-3.5 py-1.5 font-medium transition {{ $isActive ? 'border-emerald-800 bg-emerald-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900' }}">
              {{ $label }}
            </a>
          @endforeach
        </nav>
      </div>

      <div class="max-h-[calc(100vh-18rem)] overflow-y-auto px-2.5 py-2.5">
        @forelse ($conversations as $conversation)
          @php
            $title = $conversationTitle($conversation);
            $subtitle = $conversationSubtitle($conversation);
            $latest = $conversation->latest_message ?? $conversation->messages->sortByDesc('created_at')->first();
            $isActiveConversation = $selectedConversation && $selectedConversation->id === $conversation->id;
          @endphp
          <a href="{{ route('messages.show', array_merge(['conversation' => $conversation], request()->except('page'))) }}" class="mb-1.5 block rounded-2xl border px-3.5 py-3 transition {{ $isActiveConversation ? 'border-emerald-200 bg-emerald-50 shadow-sm' : 'border-transparent bg-white hover:border-slate-200 hover:bg-slate-50' }}">
            <div class="flex items-start gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white">
                {{ $conversationInitials($title) }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="truncate text-[15px] font-semibold text-slate-950">{{ $title }}</p>
                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ $subtitle }}</p>
                  </div>
                  <div class="shrink-0 text-right">
                    <p class="text-xs text-slate-500">{{ $conversation->updated_at->format($conversation->updated_at->isToday() ? 'H:i' : 'D') }}</p>
                    @if (($conversation->unread_count ?? 0) > 0)
                      <span class="mt-1 inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                        {{ $conversation->unread_count }} new
                      </span>
                    @endif
                  </div>
                </div>

                @if ($latest)
                  <p class="mt-1.5 line-clamp-2 text-[13px] leading-5 {{ ($conversation->unread_count ?? 0) > 0 ? 'font-medium text-slate-800' : 'text-slate-600' }}">
                    @if (! $latest->is_system)
                      <span class="font-semibold">{{ $latest->sender->name }}:</span>
                    @endif
                    @if (filled($latest->body))
                      {{ \Illuminate\Support\Str::limit($latest->body, 84) }}
                    @elseif ($latest->attachments->isNotEmpty())
                      {{ $latest->attachments->count() === 1 ? 'Sent an attachment' : 'Sent ' . $latest->attachments->count() . ' attachments' }}
                    @else
                      No message text
                    @endif
                  </p>
                @else
                  <p class="mt-2 text-sm text-slate-500">No messages yet.</p>
                @endif

                <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                  <span class="inline-flex items-center rounded-full px-2.5 py-1 font-semibold {{ $typeBadgeClass($conversation->type) }}">
                    {{ $filterOptions[$conversation->type] ?? ucfirst($conversation->type) }}
                  </span>
                  @if ($conversation->department)
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">
                      {{ $conversation->department->name }}
                    </span>
                  @endif
                  @if ($conversation->is_locked)
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-700">
                      Replies Locked
                    </span>
                  @endif
                </div>
              </div>
            </div>
          </a>
        @empty
          <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
            @if ($search !== '')
              No conversations matched your search.
            @else
              No conversations yet.
            @endif
          </div>
        @endforelse
      </div>

      <div class="border-t border-slate-200 px-4 py-2.5">
        {{ $conversations->links() }}
      </div>
    </aside>

    <section class="min-w-0 rounded-[24px] border border-slate-200 bg-white shadow-sm {{ $selectedConversation ? 'block' : 'hidden xl:block' }}">
      @if ($selectedConversation)
        @php
          $selectedTitle = $conversationTitle($selectedConversation);
          $selectedSubtitle = $conversationSubtitle($selectedConversation);
          $currentMessageDate = null;
          $managerOnly = $selectedConversation->participants->every(fn ($participant) => $participant->hasRole('manager', 'ops_manager', 'hr', 'admin'));
        @endphp
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-4 py-3.5">
          <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('messages.index', request()->except('page')) }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900 xl:hidden" aria-label="Back to all chats">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M11.78 4.22a.75.75 0 010 1.06L7.56 9.5h8.19a.75.75 0 010 1.5H7.56l4.22 4.22a.75.75 0 11-1.06 1.06l-5.5-5.5a.75.75 0 010-1.06l5.5-5.5a.75.75 0 011.06 0z" clip-rule="evenodd" />
              </svg>
            </a>
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white">
              {{ $conversationInitials($selectedTitle) }}
            </div>
            <div class="min-w-0">
              <h2 class="truncate text-xl font-semibold tracking-tight text-slate-950">{{ $selectedTitle }}</h2>
              <p class="mt-0.5 truncate text-sm text-slate-500">{{ $selectedSubtitle }}</p>
              <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $typeBadgeClass($selectedConversation->type) }}">
                  {{ $filterOptions[$selectedConversation->type] ?? ucfirst($selectedConversation->type) }}
                </span>
                @if ($selectedConversation->department)
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">{{ $selectedConversation->department->name }}</span>
                @endif
                @if ($managerOnly)
                  <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 font-semibold text-purple-700">Manager Only</span>
                @endif
                @if ($selectedConversation->is_locked)
                  <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 font-semibold text-rose-700">Replies Locked</span>
                @endif
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            @can('lock', $selectedConversation)
              <form method="POST" action="{{ route('messages.lock', $selectedConversation) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="lock" value="{{ $selectedConversation->is_locked ? 0 : 1 }}">
                <button type="submit" class="inline-flex items-center rounded-full {{ $selectedConversation->is_locked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-3.5 py-1.5 text-sm font-semibold">
                  {{ $selectedConversation->is_locked ? 'Unlock Replies' : 'Lock Replies' }}
                </button>
              </form>
            @endcan
            <a href="{{ route('messages.index', request()->except('page')) }}" class="hidden xl:inline-flex items-center rounded-full border border-slate-200 px-3.5 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">All chats</a>
          </div>
        </header>

        <div class="space-y-3 bg-slate-950/95 px-4 py-4">
          <div class="max-h-[calc(100vh-21rem)] overflow-y-auto pr-1">
            @forelse ($selectedConversation->messages as $message)
              @php
                $messageDate = $message->created_at->toDateString();
                $showDateDivider = $currentMessageDate !== $messageDate;
                $currentMessageDate = $messageDate;
                $isOwn = $message->sender_id === $currentUser->id;
              @endphp

              @if ($showDateDivider)
                <div class="my-3 flex justify-center">
                  <span class="rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-200">
                    {{ $message->created_at->isToday() ? 'Today' : ($message->created_at->isYesterday() ? 'Yesterday' : $message->created_at->format('M j')) }}
                  </span>
                </div>
              @endif

              <div class="mb-2.5 flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[min(42rem,85%)]">
                  @if (! $message->is_system && ! $isOwn && $selectedConversation->type !== 'direct')
                    <p class="mb-1 px-1 text-xs font-semibold text-slate-300">{{ $message->sender->name }}</p>
                  @endif
                  <div class="rounded-[20px] px-4 py-2.5 shadow-sm {{ $message->is_system ? 'border border-amber-200 bg-amber-50 text-slate-900' : ($isOwn ? 'bg-emerald-800 text-white' : 'bg-white/10 text-white ring-1 ring-white/10') }}">
                    @if (filled($message->body))
                      <p class="whitespace-pre-line text-sm leading-[1.375rem]">{{ $message->body }}</p>
                    @endif
                    @if ($message->attachments->isNotEmpty())
                      <div class="{{ filled($message->body) ? 'mt-3' : '' }} space-y-2">
                        @foreach ($message->attachments as $attachment)
                          @if ($attachment->isImage())
                            <a href="{{ $attachment->download_url }}" class="block overflow-hidden rounded-2xl {{ $message->is_system ? 'border border-amber-200 bg-amber-100' : ($isOwn ? 'bg-emerald-700' : 'bg-white/10 ring-1 ring-white/10') }}">
                              <img src="{{ $attachment->preview_url }}" alt="{{ $attachment->original_name }}" class="max-h-72 w-full object-cover">
                              <div class="flex items-center justify-between gap-3 px-3 py-2">
                                <div class="min-w-0">
                                  <p class="truncate text-sm font-semibold {{ $message->is_system ? 'text-slate-900' : 'text-white' }}">{{ $attachment->original_name }}</p>
                                  <p class="text-xs {{ $message->is_system ? 'text-slate-500' : ($isOwn ? 'text-emerald-100' : 'text-slate-300') }}">
                                    {{ number_format(max(1, $attachment->size) / 1024, 1) }} KB · Image
                                  </p>
                                </div>
                                <span class="shrink-0 text-xs font-semibold {{ $message->is_system ? 'text-slate-600' : ($isOwn ? 'text-emerald-100' : 'text-slate-200') }}">Open</span>
                              </div>
                            </a>
                          @else
                          <a href="{{ $attachment->download_url }}" class="flex items-center gap-3 rounded-2xl px-3 py-2 transition {{ $message->is_system ? 'bg-amber-100 hover:bg-amber-200' : ($isOwn ? 'bg-emerald-700 hover:bg-emerald-600' : 'bg-white/10 hover:bg-white/15') }}">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $message->is_system ? 'bg-white text-amber-700' : ($isOwn ? 'bg-emerald-900 text-white' : 'bg-white/15 text-white') }}">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                  <path d="M4.75 2.5A1.75 1.75 0 003 4.25v11.5C3 16.44 3.56 17 4.25 17h11.5c.69 0 1.25-.56 1.25-1.25v-8.5a.75.75 0 00-.22-.53l-3.5-3.5A.75.75 0 0012.75 3h-8z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                              <p class="truncate text-sm font-semibold {{ $message->is_system ? 'text-slate-900' : 'text-white' }}">{{ $attachment->original_name }}</p>
                              <p class="text-xs {{ $message->is_system ? 'text-slate-500' : ($isOwn ? 'text-emerald-100' : 'text-slate-300') }}">
                                {{ number_format(max(1, $attachment->size) / 1024, 1) }} KB · File
                              </p>
                            </div>
                          </a>
                          @endif
                        @endforeach
                      </div>
                    @endif
                    <div class="mt-1.5 flex items-center justify-end gap-2 text-[11px] {{ $message->is_system ? 'text-slate-500' : ($isOwn ? 'text-emerald-100' : 'text-slate-300') }}">
                      <span>{{ $message->created_at->format('H:i') }}</span>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <p class="text-sm text-slate-300">No messages yet.</p>
            @endforelse
          </div>
        </div>

        @can('message', $selectedConversation)
          <div class="border-t border-slate-200 bg-white px-4 py-3">
            <form method="POST" action="{{ route('messages.messages.store', $selectedConversation) }}" class="flex items-end gap-3" enctype="multipart/form-data">
              @csrf
              <label class="sr-only" for="reply-body">Reply</label>
              <textarea id="reply-body" name="body" rows="2" class="min-h-[3.25rem] flex-1 rounded-[20px] border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-500 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200" placeholder="Type a message"></textarea>
              <label class="inline-flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-300 bg-slate-50 text-slate-600 hover:bg-slate-100" aria-label="Attach files">
                <input type="file" name="attachments[]" class="sr-only" multiple>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M8.97 3.97a3.75 3.75 0 015.303 5.303l-5.402 5.402a2.25 2.25 0 01-3.182-3.182l5.05-5.05a.75.75 0 111.06 1.06l-5.05 5.05a.75.75 0 001.061 1.061l5.402-5.402a2.25 2.25 0 10-3.182-3.182L4.98 10.079a3.75 3.75 0 105.303 5.303l4.344-4.344a.75.75 0 111.06 1.06l-4.343 4.343A5.25 5.25 0 013.918 8.99l5.05-5.05z" clip-rule="evenodd" />
                </svg>
              </label>
              <button type="submit" class="inline-flex h-11 items-center justify-center rounded-full bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">
                Send
              </button>
            </form>
          </div>
        @else
          <div class="border-t border-slate-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
            Replies are locked for this conversation. Contact the thread owner if you need changes.
          </div>
        @endcan
      @else
        <div class="flex h-full min-h-[32rem] flex-col items-center justify-center px-6 py-10 text-center">
          <div class="flex h-20 w-20 items-center justify-center rounded-[24px] bg-slate-900 text-white shadow-sm">
            <svg class="h-9 w-9" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path d="M2 5.75A1.75 1.75 0 013.75 4h12.5A1.75 1.75 0 0118 5.75v7.5A1.75 1.75 0 0116.25 15H6.31l-2.78 2.29A.75.75 0 012 16.71V5.75z" />
            </svg>
          </div>
          <p class="mt-6 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Messenger</p>
          <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Choose a chat or start a new one</h2>
          <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">
            The inbox on the left works like a familiar chat list. Open an existing thread, or start a new one from the plus button in the inbox header.
          </p>
          @can('create', App\Models\Conversation::class)
            <button type="button" x-on:click="composeOpen = true" class="mt-5 inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.25 3a.75.75 0 00-1.5 0v5.25H2.5a.75.75 0 000 1.5h5.25V15a.75.75 0 001.5 0V9.75H14.5a.75.75 0 000-1.5H9.25V3z" />
              </svg>
              New chat
            </button>
          @endcan
        </div>
      @endif
    </section>
  </div>

  @can('create', App\Models\Conversation::class)
    <div x-cloak x-show="composeOpen" x-on:keydown.escape.window="composeOpen = false" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-950/55 px-4 py-8 sm:items-center">
      <div x-on:click.outside="composeOpen = false" class="max-h-[90vh] w-full max-w-6xl overflow-y-auto rounded-[32px] border border-slate-200 bg-white shadow-2xl">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">New chat</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-950">Start a conversation</h2>
          </div>
          <button type="button" x-on:click="composeOpen = false" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900" aria-label="Close new chat">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 11-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 11-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        <div class="grid gap-5 p-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <div class="space-y-5">
            @if (($shortcutOptions ?? collect())->isNotEmpty())
              <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                  <h3 class="text-xl font-semibold text-slate-950">Quick Contact</h3>
                  <p class="mt-1 text-sm text-slate-600">Start the most common routed chats without searching for a person first.</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                  @foreach ($shortcutOptions as $shortcut)
                    <form method="POST" action="{{ route('messages.store') }}" class="rounded-[20px] border border-slate-200 bg-slate-50 p-4" enctype="multipart/form-data">
                      @csrf
                      <input type="hidden" name="shortcut" value="{{ $shortcut['key'] }}">
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <h4 class="text-base font-semibold text-slate-950">{{ $shortcut['label'] }}</h4>
                          <p class="mt-1 text-xs font-medium text-slate-500">{{ ucfirst(str_replace('_', ' ', $shortcut['recipient_role'])) }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-xs font-semibold text-white">
                          {{ $conversationInitials($shortcut['recipient_name']) }}
                        </div>
                      </div>
                      <p class="mt-3 text-sm text-slate-600">{{ $shortcut['description'] }}</p>
                      <p class="mt-3 text-sm text-slate-700">Target: <span class="font-semibold">{{ $shortcut['recipient_name'] }}</span></p>
                      <label class="mt-3 block text-sm font-medium text-slate-700">
                        Message
                        <textarea name="body" rows="3" class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm">{{ old('shortcut') === $shortcut['key'] ? old('body') : '' }}</textarea>
                      </label>
                      <label class="mt-3 block text-sm font-medium text-slate-700">
                        Attach files
                        <input type="file" name="attachments[]" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" multiple>
                      </label>
                      <button type="submit" class="mt-3 inline-flex w-full justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Start chat
                      </button>
                    </form>
                  @endforeach
                </div>
              </section>
            @endif

            <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
              <h3 class="text-xl font-semibold text-slate-950">Start Conversation</h3>
              <p class="mt-1 text-sm text-slate-600">Open a direct chat with one or more people.</p>
              <form method="POST" action="{{ route('messages.store') }}" class="mt-4 space-y-4" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="direct">
                <label class="block text-sm font-medium text-slate-700">
                  Recipients
                  <select name="recipients[]" class="mt-1 w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm" multiple size="5">
                    @foreach ($recipientOptions as $recipient)
                      <option value="{{ $recipient->id }}">{{ $recipient->name }} · {{ ucfirst((string) ($recipient->role?->value ?? $recipient->role)) }}</option>
                    @endforeach
                  </select>
                </label>
                @error('recipients')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <label class="block text-sm font-medium text-slate-700">
                  Message
                  <textarea name="body" rows="4" class="mt-1 w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm"></textarea>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                  Attach files
                  <input type="file" name="attachments[]" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" multiple>
                </label>
                <button type="submit" class="inline-flex rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Send</button>
              </form>
            </section>
          </div>

          <div class="space-y-5">
            @if ($managedDepartmentOptions->isNotEmpty())
              <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-950">Department Broadcast</h3>
                <p class="mt-1 text-sm text-slate-600">Send one message to a managed department thread.</p>
                <form method="POST" action="{{ route('messages.store') }}" class="mt-4 space-y-4" enctype="multipart/form-data">
                  @csrf
                  <input type="hidden" name="type" value="department">
                  <label class="block text-sm font-medium text-slate-700">
                    Department
                    <select name="department_id" class="mt-1 w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm" required>
                      <option value="">Select department</option>
                      @foreach ($managedDepartmentOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                      @endforeach
                    </select>
                  </label>
                  @error('department_id')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                  @enderror
                  <label class="block text-sm font-medium text-slate-700">
                    Subject
                    <input type="text" name="subject" class="mt-1 w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm" placeholder="Optional subject" />
                  </label>
                  <label class="block text-sm font-medium text-slate-700">
                    Message
                    <textarea name="body" rows="4" class="mt-1 w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm"></textarea>
                  </label>
                  <label class="block text-sm font-medium text-slate-700">
                    Attach files
                    <input type="file" name="attachments[]" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" multiple>
                  </label>
                  <button type="submit" class="inline-flex w-full justify-center rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Send Broadcast</button>
                </form>
              </section>
            @endif

            <section class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
              <h3 class="text-lg font-semibold text-slate-950">How this works</h3>
              <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                <li>Direct chats behave like a normal messenger thread.</li>
                <li>Department conversations are broadcast-style updates for a full team.</li>
                <li>Announcement threads can be locked when replies should stay closed.</li>
              </ul>
            </section>
          </div>
        </div>
      </div>
    </div>
  @endcan
</div>
