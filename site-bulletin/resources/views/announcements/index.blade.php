@extends('layouts.app')

@section('content')
  @php
      $priorityClasses = [
          'urgent' => 'bg-rose-100 text-rose-700',
          'high' => 'bg-amber-100 text-amber-700',
          'medium' => 'bg-blue-100 text-blue-700',
          'low' => 'bg-slate-100 text-slate-700',
      ];
  @endphp

  <div class="space-y-6" x-data="{ showComposer: {{ $errors->any() ? 'true' : 'false' }} }" @keydown.escape.window="showComposer = false">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Announcements</h1>
        <p class="text-sm text-slate-600">Search, filter, and track unread site updates.</p>
      </div>
      <div class="flex items-center gap-2">
        @if (!empty($canCreate))
          <button @click="showComposer = !showComposer" type="button" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            <span x-show="!showComposer">New Announcement</span>
            <span x-show="showComposer">Hide Composer</span>
          </button>
        @endif
        @if (($stats['unread'] ?? 0) > 0)
          <form method="POST" action="{{ route('announcements.read-all') }}">
            @csrf
            <button type="submit" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Mark All as Read
            </button>
          </form>
        @endif
      </div>
    </div>

    @if (!empty($canCreate))
      <div
        x-show="showComposer"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="showComposer = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-announcement-title"
        style="display: none;"
      >
        <div class="w-full max-w-3xl rounded-xl border border-slate-200 bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <div>
              <h2 id="create-announcement-title" class="text-lg font-semibold text-slate-900">Create Announcement</h2>
              <p class="mt-1 text-sm text-slate-600">Send an update to your allowed audience.</p>
            </div>
            <button type="button" @click="showComposer = false" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
          <form method="POST" action="{{ route('announcements.store') }}" class="space-y-4 px-5 py-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-1 md:col-span-2">
                <label for="title" class="text-sm font-medium text-slate-700">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>
              <div class="space-y-1 md:col-span-2">
                <label for="body" class="text-sm font-medium text-slate-700">Message</label>
                <textarea id="body" name="body" rows="4" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body') }}</textarea>
                @error('body')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>
              <div class="space-y-1">
                <label for="priority" class="text-sm font-medium text-slate-700">Priority</label>
                <select id="priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="space-y-1">
                <label for="audience" class="text-sm font-medium text-slate-700">Audience</label>
                <select id="audience" name="audience" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  @foreach (($audienceOptions ?? []) as $value => $label)
                    <option value="{{ $value }}" @selected(old('audience', array_key_first($audienceOptions ?? ['department' => 'Specific Department'])) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @error('audience')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>
              <div class="space-y-1">
                <label for="department_id" class="text-sm font-medium text-slate-700">Department (for department audience)</label>
                <select id="department_id" name="department_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  <option value="">Select department</option>
                  @foreach (($departmentOptions ?? collect()) as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('department_id') === (string) $id)>{{ $name }}</option>
                  @endforeach
                </select>
                @error('department_id')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>
              <div class="space-y-1">
                <label for="starts_at" class="text-sm font-medium text-slate-700">Start (optional)</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
              </div>
              <div class="space-y-1">
                <label for="ends_at" class="text-sm font-medium text-slate-700">End (optional)</label>
                <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('ends_at')
                  <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
              </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
              <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned')) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
              Pin announcement
            </label>
            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-3">
              <button type="button" @click="showComposer = false" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
              </button>
              <button type="submit" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Send Announcement
              </button>
            </div>
          </form>
        </div>
      </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['total'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Unread</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['unread'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">High Priority</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['high_priority'] ?? 0 }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">My Department</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $stats['my_department'] ?? 0 }}</p>
      </div>
    </section>

    <form method="GET" action="{{ route('announcements.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
      <div class="grid gap-3 lg:grid-cols-4">
        <input
          type="search"
          name="search"
          value="{{ $search }}"
          placeholder="Search announcements..."
          aria-label="Search announcements"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-2"
        />
        <select name="sort" aria-label="Sort announcements" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="newest" @selected($sort === 'newest')>Newest First</option>
          <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
          <option value="priority" @selected($sort === 'priority')>Priority</option>
          <option value="unread" @selected($sort === 'unread')>Unread First</option>
        </select>
        <select name="filter" aria-label="Filter announcements" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="all" @selected($filter === 'all')>All Announcements</option>
          <option value="unread" @selected($filter === 'unread')>Unread Only</option>
          <option value="high-priority" @selected($filter === 'high-priority')>High Priority</option>
          <option value="my-department" @selected($filter === 'my-department')>My Department</option>
        </select>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <button type="submit" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
          Apply
        </button>
        <a href="{{ route('announcements.index') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
          Reset
        </a>
      </div>
    </form>

    <section class="space-y-4">
      @forelse ($announcements as $announcement)
        @php
            $isRead = (bool) ($announcement->is_read ?? false);
            $priority = $announcement->priority ?? 'medium';
        @endphp
        <article class="rounded-xl border px-5 py-4 shadow-sm {{ $isRead ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50' }}">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-base font-semibold text-slate-900">
                <a href="{{ route('announcements.show', $announcement) }}" class="hover:underline">
                  {{ $announcement->title }}
                </a>
              </h2>
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
            <p class="text-xs text-slate-500">Updated {{ $announcement->updated_at?->diffForHumans() ?? 'recently' }}</p>
          </div>

          @if ($announcement->body)
            <p class="mt-2 text-sm text-slate-700">{{ $announcement->body }}</p>
          @endif

          <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
            <p>
              From {{ $announcement->author->name ?? 'System' }}
              @if ($announcement->starts_at)
                · Published {{ $announcement->starts_at->format('M j, Y H:i') }}
              @endif
            </p>
            <div class="flex items-center gap-2">
              @unless ($isRead)
                <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                  Unread
                </span>
              @endunless
              <a href="{{ route('announcements.show', $announcement) }}" class="inline-flex items-center rounded-full border border-slate-300 bg-white px-3 py-1 font-semibold text-slate-700 hover:bg-slate-50">
                Open
              </a>
            </div>
          </div>
        </article>
      @empty
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-slate-500">
          No announcements found for your current filters.
          <div class="mt-3">
            <a href="{{ route('announcements.index') }}" class="text-sm font-medium text-blue-600 hover:underline">Clear filters</a>
          </div>
        </div>
      @endforelse
    </section>

    <div>
      {{ $announcements->links() }}
    </div>
  </div>
@endsection
