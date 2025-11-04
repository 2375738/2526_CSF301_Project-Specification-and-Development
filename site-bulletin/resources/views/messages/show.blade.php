@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Conversation</p>
        <h1 class="text-2xl font-semibold text-slate-900">{{ $conversation->subject ?? __('(No subject)') }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
          <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-0.5 font-medium text-slate-600">
            {{ ucfirst($conversation->type) }}
          </span>
          @if ($conversation->department)
            <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-0.5 font-medium text-slate-600">{{ $conversation->department->name }}</span>
          @endif
          @php
            $managerOnly = $conversation->participants->every(fn ($participant) => $participant->hasRole('manager', 'ops_manager', 'hr', 'admin'));
          @endphp
          @if ($managerOnly)
            <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-0.5 font-semibold text-purple-700">Manager Only</span>
          @endif
          @if ($conversation->is_locked)
            <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-0.5 font-semibold text-rose-700">Replies Locked</span>
          @endif
        </div>
        <p class="text-sm text-slate-600">
          {{ $conversation->participants->pluck('name')->implode(', ') }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        @can('lock', $conversation)
          <form method="POST" action="{{ route('messages.lock', $conversation) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="lock" value="{{ $conversation->is_locked ? 0 : 1 }}">
            <button type="submit"
                    class="inline-flex items-center rounded-full {{ $conversation->is_locked ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} px-3 py-1 text-xs font-semibold uppercase">
              {{ $conversation->is_locked ? 'Unlock Replies' : 'Lock Replies' }}
            </button>
          </form>
        @endcan
        <a href="{{ route('messages.index') }}" class="text-sm font-medium text-blue-600 hover:underline">Back to Messages</a>
      </div>
    </div>

    @if (session('status'))
      <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
      @forelse ($conversation->messages as $message)
        <div class="rounded-lg border border-slate-200 px-4 py-3">
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span class="font-semibold text-slate-800">{{ $message->sender->name }}</span>
            <span>{{ $message->created_at->format('M j, H:i') }}</span>
          </div>
          <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ $message->body }}</p>
        </div>
      @empty
        <p class="text-sm text-slate-500">No messages yet.</p>
      @endforelse
    </div>

    @can('message', $conversation)
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Reply</h2>
        <form method="POST" action="{{ route('messages.messages.store', $conversation) }}" class="mt-3 space-y-3">
          @csrf
          <textarea name="body" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required></textarea>
          <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send</button>
        </form>
      </div>
    @else
      <div class="rounded-xl border border-slate-200 bg-rose-50 p-5 shadow-sm text-sm text-rose-700">
        Replies are locked for this conversation. Contact the thread owner if you need changes.
      </div>
    @endcan
  </div>
@endsection
