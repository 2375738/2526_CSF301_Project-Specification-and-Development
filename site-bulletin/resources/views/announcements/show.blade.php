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

