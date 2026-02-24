@extends('layouts.app')

@section('content')
  @php
    $filterOptions = [
      '' => 'All',
      'direct' => 'Direct',
      'department' => 'Department',
      'announcement' => 'Announcements',
    ];
    $requestedType = request('type');
    $activeType = $activeType ?? (array_key_exists($requestedType, $filterOptions) ? $requestedType : null);
    $search = $search ?? request('q', '');
  @endphp

  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Inbox</p>
        <h1 class="text-2xl font-semibold text-slate-900">Messages</h1>
        <p class="text-sm text-slate-600">Manage direct chats and department broadcasts.</p>
      </div>
      <a href="{{ route('home') }}" class="text-sm font-medium text-blue-600 hover:underline">Back to Dashboard</a>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Recent Chats</h2>
          <p class="text-xs text-slate-500">{{ $unreadConversationCount ?? 0 }} unread conversation{{ ($unreadConversationCount ?? 0) === 1 ? '' : 's' }}.</p>
        </div>
      </div>
      @if (($previewConversations ?? collect())->isEmpty())
        <p class="text-sm text-slate-500">No recent chats yet.</p>
      @else
        <div class="grid gap-3 md:grid-cols-3">
          @foreach ($previewConversations as $conversation)
            @php
              $latest = $conversation->messages->first();
              $isUnread = ($conversation->unread_count ?? 0) > 0;
            @endphp
            <article class="rounded-lg border px-4 py-3 {{ $isUnread ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-white' }}">
              <div class="flex items-start justify-between gap-2">
                <a href="{{ route('messages.show', $conversation) }}" class="font-semibold text-slate-900 hover:underline line-clamp-1">
                  {{ $conversation->subject ?? __('(No subject)') }}
                </a>
                @if ($isUnread)
                  <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                    {{ $conversation->unread_count }} new
                  </span>
                @endif
              </div>
              @if ($latest)
                <p class="mt-2 text-xs text-slate-600 line-clamp-2">
                  <span class="font-semibold">{{ $latest->sender->name }}:</span>
                  {{ \Illuminate\Support\Str::limit($latest->body, 90) }}
                </p>
              @endif
              <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ ucfirst($conversation->type) }}</span>
                <span>{{ $conversation->updated_at->diffForHumans() }}</span>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </section>

    @if (session('status'))
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    <form method="GET" action="{{ route('messages.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm flex flex-wrap items-center gap-2">
      <input type="search" name="q" value="{{ $search }}" placeholder="Search subjects, participants, or messages..." aria-label="Search messages" class="w-full md:flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
      @if ($activeType)
        <input type="hidden" name="type" value="{{ $activeType }}">
      @endif
      <button type="submit" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
        Search
      </button>
      @if ($search !== '')
        <a href="{{ route('messages.index', array_filter(['type' => $activeType])) }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Clear
        </a>
      @endif
    </form>

    <nav class="flex flex-wrap items-center gap-2 text-sm">
      @foreach ($filterOptions as $value => $label)
        @php
          $isActive = ($activeType ?? '') === $value || ($value === '' && empty($activeType));
          $query = collect(request()->except(['page', 'type']))->when($value !== '', fn ($q) => $q->put('type', $value))->all();
        @endphp
        <a
          href="{{ route('messages.index', $query) }}"
          @if ($isActive) aria-current="page" @endif
          class="inline-flex items-center rounded-full border px-4 py-1 font-medium transition
            {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900' }}">
          {{ $label }}
        </a>
      @endforeach
    </nav>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="lg:col-span-2 space-y-4">
        @forelse ($conversations as $conversation)
          @php
            $latest = $conversation->messages->first();
            $managerOnly = $conversation->participants->every(fn ($participant) => $participant->hasRole('manager', 'ops_manager', 'hr', 'admin'));
          @endphp
          <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex items-center justify-between">
              <a href="{{ route('messages.show', $conversation) }}" class="text-base font-semibold text-slate-900 hover:underline">
                {{ $conversation->subject ?? __('(No subject)') }}
              </a>
              <div class="flex flex-wrap items-center gap-2">
                @if ($conversation->type === 'direct')
                  <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-0.5 text-xs font-medium text-slate-600">Direct</span>
                @elseif ($conversation->type === 'department')
                  <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-semibold text-blue-700">Department Broadcast</span>
                @elseif ($conversation->type === 'announcement')
                  <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-700">Announcement</span>
                @endif
                @if ($conversation->department)
                  <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-0.5 text-xs font-medium text-slate-600">{{ $conversation->department->name }}</span>
                @endif
                @if ($managerOnly)
                  <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-0.5 text-xs font-semibold text-purple-700">Manager Only</span>
                @endif
                @if ($conversation->is_locked)
                  <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-700">Replies Locked</span>
                @endif
              </div>
            </div>
            <p class="mt-1 text-xs text-slate-500">Updated {{ $conversation->updated_at->diffForHumans() }}</p>
            @if ($latest)
              <p class="mt-2 text-sm text-slate-600">
                <span class="font-semibold">{{ $latest->sender->name }}:</span>
                {{ \Illuminate\Support\Str::limit($latest->body, 120) }}
              </p>
            @endif
          </article>
        @empty
          <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
            @if ($search !== '')
              No conversations matched your search.
            @else
              No conversations yet.
            @endif
          </div>
        @endforelse

        <div>
          {{ $conversations->links() }}
        </div>
      </div>

      <div class="space-y-4">
        @can('create', App\Models\Conversation::class)
          <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Start Conversation</h2>
            <form method="POST" action="{{ route('messages.store') }}" class="mt-4 space-y-3">
              @csrf
              <input type="hidden" name="type" value="direct">
              <label class="block text-sm font-medium text-slate-700">Recipients</label>
              <select name="recipients[]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" multiple size="4">
                @foreach ($recipientOptions as $recipient)
                  <option value="{{ $recipient->id }}">{{ $recipient->name }}</option>
                @endforeach
              </select>
              @error('recipients')
                <p class="text-xs text-rose-600">{{ $message }}</p>
              @enderror
              <label class="block text-sm font-medium text-slate-700">Message</label>
              <textarea name="body" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required></textarea>
              <button type="submit" class="inline-flex w-full justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send</button>
            </form>
          </div>

          @if ($managedDepartmentOptions->isNotEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
              <h2 class="text-lg font-semibold text-slate-900">Department Broadcast</h2>
              <form method="POST" action="{{ route('messages.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="type" value="department">
                <label class="block text-sm font-medium text-slate-700">Department</label>
                <select name="department_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                  <option value="">Select department</option>
                  @foreach ($managedDepartmentOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                  @endforeach
                </select>
                @error('department_id')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <label class="block text-sm font-medium text-slate-700">Subject</label>
                <input type="text" name="subject" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Optional subject" />
                <label class="block text-sm font-medium text-slate-700">Message</label>
                <textarea name="body" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required></textarea>
                <button type="submit" class="inline-flex w-full justify-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Send Broadcast</button>
              </form>
            </div>
          @endif
        @endcan
      </div>
    </div>
  </div>
@endsection
