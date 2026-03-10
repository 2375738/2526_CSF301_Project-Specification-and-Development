@extends('layouts.app')

@section('content')
  @php
      $priorityClasses = [
          'urgent' => 'bg-rose-100 text-rose-700',
          'high' => 'bg-amber-100 text-amber-700',
          'medium' => 'bg-blue-100 text-blue-700',
          'low' => 'bg-slate-100 text-slate-700',
      ];
      $priority = $announcement->priority ?? 'medium';
      $acknowledgementLabels = [
          'understood' => 'Understood',
          'needs_clarification' => 'Needs clarification',
      ];
      $currentAcknowledgement = $receipt->acknowledgement ?? null;
  @endphp

  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <a href="{{ route('announcements.index') }}" class="text-sm font-medium text-blue-600 hover:underline">
        Back to announcements
      </a>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $announcement->title }}</h1>
        <span class="inline-flex items-center rounded-full px-3 py-0.5 text-xs font-semibold uppercase {{ $priorityClasses[$priority] ?? $priorityClasses['medium'] }}">
          {{ $priority }}
        </span>
        @if ($announcement->department)
          <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-700">
            {{ $announcement->department->name }}
          </span>
        @elseif ($announcement->audience === 'all')
          <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-xs font-medium text-slate-700">
            All Departments
          </span>
        @endif
      </div>

      <div class="mt-2 text-xs text-slate-500">
        <span>From {{ $announcement->author->name ?? 'System' }}</span>
        @if ($announcement->starts_at)
          <span> · Published {{ $announcement->starts_at->format('M j, Y H:i') }}</span>
        @endif
        @if ($announcement->updated_at)
          <span> · Updated {{ $announcement->updated_at->diffForHumans() }}</span>
        @endif
      </div>

      <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-800 whitespace-pre-line">
        {{ $announcement->body }}
      </div>
    </article>

    <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Acknowledge This Update</h2>
          <p class="mt-1 text-sm text-slate-600">Record whether this update is clear or whether you need follow-up.</p>
        </div>
        <div class="text-sm text-slate-600">
          <p>Read state: <span class="font-semibold text-slate-800">{{ $receipt?->read_at ? 'Read' : 'Unread' }}</span></p>
          <p>
            Current acknowledgement:
            <span class="font-semibold text-slate-800">{{ $acknowledgementLabels[$currentAcknowledgement] ?? 'Read only' }}</span>
          </p>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-3">
        <form method="POST" action="{{ route('announcements.acknowledge', $announcement) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="acknowledgement" value="understood">
          <button
            type="submit"
            class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $currentAcknowledgement === 'understood' ? 'bg-emerald-600 text-white' : 'border border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50' }}"
          >
            Understood
          </button>
        </form>

        <form method="POST" action="{{ route('announcements.acknowledge', $announcement) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="acknowledgement" value="needs_clarification">
          <button
            type="submit"
            class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold {{ $currentAcknowledgement === 'needs_clarification' ? 'bg-amber-600 text-white' : 'border border-amber-300 bg-white text-amber-700 hover:bg-amber-50' }}"
          >
            Need Clarification
          </button>
        </form>
      </div>

      <p class="mt-3 text-xs text-slate-500">
        This keeps the update marked as read and helps managers see whether the message landed cleanly.
      </p>

      @if ($currentAcknowledgement === 'needs_clarification')
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
          <p class="font-semibold">Clarification requested</p>
          <p class="mt-1">
            If you still need help after acknowledging this update, use <a href="{{ route('messages.index') }}" class="font-semibold underline">Messages</a> to contact your manager or support.
          </p>
        </div>
      @endif
    </section>

    @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
      <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Acknowledgement Summary</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-4">
          <div class="rounded-lg border border-slate-200 px-4 py-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Read</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $acknowledgementSummary['read'] }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 px-4 py-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Understood</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700">{{ $acknowledgementSummary['understood'] }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 px-4 py-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Need Clarification</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $acknowledgementSummary['needs_clarification'] }}</p>
          </div>
          <div class="rounded-lg border border-slate-200 px-4 py-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Read Only</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $acknowledgementSummary['read_only'] }}</p>
          </div>
        </div>
      </section>
    @endif

    @if ($relatedAnnouncements->isNotEmpty())
      <section class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Related Updates</h2>
        <ul class="mt-4 space-y-3">
          @foreach ($relatedAnnouncements as $related)
            <li class="rounded-lg border border-slate-200 px-4 py-3">
              <a href="{{ route('announcements.show', $related) }}" class="font-semibold text-slate-900 hover:underline">
                {{ $related->title }}
              </a>
              <p class="mt-1 text-xs text-slate-500">
                {{ $related->author->name ?? 'System' }} · {{ $related->updated_at?->diffForHumans() ?? 'recently' }}
              </p>
            </li>
          @endforeach
        </ul>
      </section>
    @endif
  </div>
@endsection
