@auth
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">Latest News &amp; Updates</h2>
        <p class="text-xs text-slate-500">
          {{ $unreadAnnouncementCount ?? 0 }} unread · {{ $highPriorityAnnouncementCount ?? 0 }} high priority
        </p>
      </div>
      <a href="{{ route('announcements.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
        View all
      </a>
    </header>

    @if (($newsAnnouncements ?? collect())->isEmpty())
      <p class="text-sm text-slate-500">No recent updates available.</p>
    @else
      <ul class="space-y-3">
        @foreach ($newsAnnouncements as $announcement)
          @php
            $priority = $announcement->priority ?? 'medium';
            $isRead = (bool) ($announcement->is_read ?? false);
            $priorityClasses = match ($priority) {
                'urgent' => 'bg-rose-100 text-rose-700',
                'high' => 'bg-amber-100 text-amber-700',
                'low' => 'bg-slate-100 text-slate-700',
                default => 'bg-blue-100 text-blue-700',
            };
          @endphp
          <li class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-4 transition hover:border-blue-200 hover:bg-white">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                    News
                  </span>
                  <a href="{{ route('announcements.show', $announcement) }}" class="font-semibold text-slate-900 hover:text-blue-700 hover:underline">
                    {{ $announcement->title }}
                  </a>
                </div>
                <p class="text-xs text-slate-500">
                  {{ $announcement->author->name ?? 'System' }} · {{ $announcement->updated_at?->diffForHumans() ?? 'recently' }}
                </p>
                @if ($announcement->body)
                  <p class="text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->body, 120) }}</p>
                @endif
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $priorityClasses }}">
                  {{ $priority }}
                </span>
                @unless ($isRead)
                  <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                    Unread
                  </span>
                @endunless
              </div>
            </div>
            <div class="mt-3 flex justify-end">
              <a href="{{ route('announcements.show', $announcement) }}" class="text-xs font-semibold text-blue-600 hover:underline">
                Open story
              </a>
            </div>
          </li>
        @endforeach
      </ul>
    @endif
  </section>
@endauth
