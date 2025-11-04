@can('viewAny', \App\Models\Conversation::class)
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Messages</h2>
        <p class="text-xs text-slate-500">{{ $unreadConversationCount }} unread conversation{{ $unreadConversationCount === 1 ? '' : 's' }}.</p>
      </div>
      <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
        Inbox
        @if ($unreadConversationCount > 0)
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white">{{ $unreadConversationCount }}</span>
        @endif
      </a>
    </header>
    <ul class="space-y-3">
      @forelse($messagePreview as $conversation)
        @php
          $latest = $conversation->messages->first();
        @endphp
        <li class="rounded-lg border border-slate-200 px-4 py-3">
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold text-slate-800">
              {{ $conversation->subject ?? __('(No subject)') }}
            </span>
            <span>{{ $conversation->updated_at->diffForHumans() }}</span>
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ $conversation->participants->pluck('name')->implode(', ') }}</p>
          @if ($latest)
            <p class="mt-2 text-sm text-slate-700 truncate">
              <span class="font-semibold">{{ $latest->sender->name }}:</span>
              {{ \Illuminate\Support\Str::limit($latest->body, 120) }}
            </p>
          @endif
          @if (($conversation->unread_count ?? 0) > 0)
            <span class="mt-2 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">{{ $conversation->unread_count }} unread</span>
          @endif
        </li>
      @empty
        <li class="text-sm text-slate-500">No conversations yet.</li>
      @endforelse
    </ul>
  </section>
@endcan
