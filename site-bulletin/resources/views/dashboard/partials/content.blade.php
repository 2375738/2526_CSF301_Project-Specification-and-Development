@php
    $announcementCount = $announcements->count();
    $categoryCount = $categories->count();
    $linkCount = $categories->flatMap(fn($category) => $category->links)->count();
    $snapshotCount = $snapshots->count();
    $messagePreview = $messagePreview ?? collect();
    $unreadConversationCount = $unreadConversationCount ?? 0;
    $isEmployeeDashboard = auth()->check() && auth()->user()->isEmployee() && ! empty($employeeWorkToday);
@endphp

<div class="space-y-10">
  @if (! $isEmployeeDashboard)
    @php
        $isGuestLanding = ! auth()->check();
        $heroClasses = $isGuestLanding
            ? 'border-slate-200 bg-gradient-to-r from-slate-800 via-slate-700 to-blue-800 text-white'
            : 'border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 text-white';
        $heroAccentClasses = $isGuestLanding
            ? 'bg-[radial-gradient(circle_at_top,_rgba(191,219,254,0.24),_transparent_72%)]'
            : 'bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.16),_transparent_72%)]';
    @endphp
    <section class="site-landing-hero relative overflow-hidden rounded-3xl border px-6 py-6 shadow-lg {{ $heroClasses }}">
      @if ($isGuestLanding)
        <div class="pointer-events-none absolute inset-0 opacity-90" aria-hidden="true">
          <svg class="h-full w-full" viewBox="0 0 1200 260" fill="none" preserveAspectRatio="none">
            <defs>
              <linearGradient id="guestWaveStroke" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="rgba(191,219,254,0.08)" />
                <stop offset="50%" stop-color="rgba(255,255,255,0.22)" />
                <stop offset="100%" stop-color="rgba(125,211,252,0.10)" />
              </linearGradient>
            </defs>

            <g class="landing-wave-slow">
              <path d="M0 172C96 160 152 124 247 128C342 132 406 188 499 188C591 188 651 141 745 136C853 130 911 184 1012 180C1084 177 1144 146 1200 132" stroke="url(#guestWaveStroke)" stroke-width="2.5" stroke-linecap="round"/>
            </g>
            <g class="landing-wave-fast">
              <path d="M0 208C86 221 153 235 241 228C340 220 389 170 486 166C592 161 649 205 752 206C862 206 911 169 1013 160C1089 154 1143 169 1200 180" stroke="rgba(255,255,255,0.18)" stroke-width="1.6" stroke-linecap="round"/>
            </g>

            <g class="landing-dot-cluster landing-dot-cluster-a" fill="rgba(255,255,255,0.32)">
              <circle cx="902" cy="58" r="3.5"/>
              <circle cx="938" cy="79" r="2.5"/>
              <circle cx="972" cy="51" r="2.75"/>
              <circle cx="1011" cy="72" r="2.25"/>
              <circle cx="1052" cy="44" r="3"/>
              <circle cx="1086" cy="68" r="2.5"/>
            </g>

            <g class="landing-dot-cluster landing-dot-cluster-b" fill="rgba(191,219,254,0.28)">
              <circle cx="764" cy="188" r="2.5"/>
              <circle cx="802" cy="172" r="2.25"/>
              <circle cx="842" cy="196" r="3"/>
              <circle cx="881" cy="182" r="2"/>
              <circle cx="918" cy="204" r="2.5"/>
              <circle cx="956" cy="190" r="2.25"/>
            </g>
          </svg>
        </div>
      @endif
      <div class="absolute inset-y-0 right-0 hidden w-1/4 lg:block {{ $heroAccentClasses }}"></div>
      <div class="relative z-10 flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="max-w-3xl">
          <p class="text-[11px] font-semibold uppercase tracking-[0.28em] {{ $isGuestLanding ? 'text-blue-100' : 'text-slate-300' }}">
            {{ $isGuestLanding ? 'Operations Portal' : 'Operations Overview' }}
          </p>
          <div class="mt-2 flex flex-col gap-2 xl:flex-row xl:items-end xl:gap-4">
            <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">
              {{ auth()->check() ? 'Good to see you, ' . auth()->user()->name : 'Welcome to Site Bulletin' }}
            </h1>
            @auth
              <p class="text-sm text-slate-300">
                @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                  Team visibility, queue health, and unread updates in one place.
                @else
                  Site updates, resources, and recent activity in one place.
                @endif
              </p>
            @endauth
          </div>
          @if ($isGuestLanding)
            <p class="mt-3 max-w-2xl text-sm text-slate-100">
              Sign in to check site updates, open quick resources, message your team, and track operational issues in one place.
            </p>
          @else
            <p class="mt-3 max-w-2xl text-sm text-slate-300">
              Start with the highest-priority work, then move into updates, tickets, and performance detail below.
            </p>
          @endif
        </div>
        @auth
          <div class="flex flex-wrap gap-3">
            <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 hover:bg-white hover:text-slate-900">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M9.25 3a.75.75 0 00-1.5 0v2.5H5.25a.75.75 0 000 1.5H7.75V9.5a.75.75 0 001.5 0V7h2.5a.75.75 0 000-1.5H9.25z" />
                <path d="M16.5 5A1.5 1.5 0 0118 6.5v9A1.5 1.5 0 0116.5 17h-13A1.5 1.5 0 012 15.5v-9A1.5 1.5 0 013.5 5h13z" />
              </svg>
              View Tickets
            </a>
            <a href="{{ route('messages.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 hover:bg-white hover:text-slate-900">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M2 5.75A1.75 1.75 0 013.75 4h12.5A1.75 1.75 0 0118 5.75v7.5A1.75 1.75 0 0116.25 15H6.31l-2.78 2.29A.75.75 0 012 16.71V5.75z" />
              </svg>
              Open Messages
            </a>
          </div>
        @endauth
      </div>
    </section>
  @endif

  @include('dashboard.partials.my-work-today', [
      'employeeWorkToday' => $employeeWorkToday ?? null,
  ])

  @include('dashboard.partials.next-actions', [
      'roleActions' => $roleActions ?? collect(),
  ])

  @auth
    @if (! $isEmployeeDashboard)
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Announcements</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $announcementCount }}</p>
        <p class="text-sm text-slate-500">{{ $announcementCount === 1 ? 'Update' : 'Updates' }} currently published</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quick Link Categories</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $categoryCount }}</p>
        <p class="text-sm text-slate-500">Organised resource hubs</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Individual Links</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $linkCount }}</p>
        <p class="text-sm text-slate-500">Ready-to-open destinations</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Performance Weeks</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $snapshotCount }}</p>
        <p class="text-sm text-slate-500">Recently tracked data points</p>
      </div>
    </section>
    @endif
  @endauth

  @include('dashboard.partials.manager-attention-queue', [
      'managerAttentionQueue' => $managerAttentionQueue ?? null,
  ])

  @include('dashboard.partials.prevention-panel', [
      'managerPreventionInsights' => $managerPreventionInsights ?? null,
  ])

  @include('dashboard.partials.hr-workspace', [
      'hrWorkspace' => $hrWorkspace ?? null,
  ])

  @include('dashboard.partials.trend-panels', [
      'snapshots' => $snapshots,
      'departmentMetricTrend' => $departmentMetricTrend,
  ])

  @include('dashboard.partials.messages-widget', [
      'messagePreview' => $messagePreview,
      'unreadConversationCount' => $unreadConversationCount,
  ])

  @include('dashboard.partials.governance-widget', [
      'governanceLogs' => $governanceLogs,
  ])

  @include('dashboard.partials.news-widget', [
      'newsAnnouncements' => $newsAnnouncements ?? collect(),
      'unreadAnnouncementCount' => $unreadAnnouncementCount ?? 0,
      'highPriorityAnnouncementCount' => $highPriorityAnnouncementCount ?? 0,
  ])

  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-slate-900">Quick Links</h2>
      <span class="text-xs uppercase tracking-wider text-slate-500">Open in new tab</span>
    </div>
    @if ($categories->isEmpty())
      <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-500">
        No quick links yet. Managers can add them through the admin panel.
      </div>
    @else
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($categories as $category)
          <div class="flex h-full flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Category</p>
                <h3 class="text-lg font-semibold text-slate-900">{{ $category->name }}</h3>
              </div>
              <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900/5 text-slate-600">
                <x-category-icon :name="$category->name" />
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              @if ($category->is_sensitive)
                <span class="inline-flex w-fit items-center gap-1 rounded-full bg-rose-100 px-3 py-0.5 text-xs font-semibold text-rose-600">
                  <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-.75-11a.75.75 0 011.5 0v4a.75.75 0 01-1.5 0V7zm.75 6a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                  </svg>
                  Restricted
                </span>
              @endif
              @if ($category->department)
                <span class="inline-flex w-fit items-center rounded-full bg-slate-900/5 px-3 py-0.5 text-xs font-medium text-slate-600">
                  {{ $category->department->name }}
                </span>
              @elseif ($category->audience === 'managers')
                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-medium text-blue-700">
                  Managers Only
                </span>
              @endif
            </div>
            @if ($category->audience === 'department' && ! $category->department)
              <span class="inline-flex w-fit items-center gap-1 rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-700">
                Department Specific
              </span>
            @endif
            @if ($category->links->isEmpty())
              <p class="text-sm text-slate-500">No links for this category yet.</p>
            @else
              <ul class="space-y-2 text-sm">
                @foreach ($category->links as $link)
                  <li>
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="group inline-flex w-full items-center justify-between rounded-lg border border-transparent px-3 py-2 text-slate-700 transition hover:border-slate-200 hover:bg-slate-50">
                      <span class="font-medium group-hover:text-slate-900">{{ $link->label }}</span>
                      <span class="inline-flex items-center gap-2">
                        @if ($link->is_hot)
                          <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-red-600">Hot</span>
                        @endif
                        <svg class="h-3.5 w-3.5 text-slate-400 group-hover:text-slate-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.47-7.47v4.19a.75.75 0 001.5 0v-6.5A.75.75 0 0014.5 4h-6.5a.75.75 0 000 1.5h4.19l-7.47 7.47a.75.75 0 000 1.06z" clip-rule="evenodd" />
                        </svg>
                      </span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </section>
</div>
