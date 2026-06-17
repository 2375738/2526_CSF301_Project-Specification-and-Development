<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'Site Bulletin') }}</title>
  <style>[x-cloak] { display: none !important; }</style>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="cwl1-bg cwl1-noise overflow-x-hidden text-gray-900">
  <div
    class="min-h-screen lg:flex"
    x-data="{
      sidebarCollapsed: localStorage.getItem('site-bulletin-sidebar-collapsed') === '1',
      scrolled: false,
      toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('site-bulletin-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
      },
      handleScroll() {
        this.scrolled = window.scrollY > 24;
      }
    }"
    x-init="handleScroll()"
    @scroll.window="handleScroll()"
  >
    @auth
      <aside
        class="hidden lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:self-start border-r border-slate-200 bg-white transition-all duration-200"
        :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
      >
        <div x-show="!scrolled" x-transition.opacity class="border-b border-slate-200 px-4 py-4">
          <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/cwl1-logo.jpeg') }}" alt="CWL1 logo" class="h-10 w-10 rounded-xl object-cover" />
            <div x-show="!sidebarCollapsed" x-cloak>
              <p class="text-base font-semibold text-slate-900">Site Bulletin</p>
              <p class="text-xs text-slate-500">Operations Portal</p>
            </div>
          </a>
        </div>

        <nav class="flex-1 space-y-1 p-3 text-sm font-medium">
          @foreach ($desktopNavItems as $item)
            <a
              href="{{ $item['route'] }}"
              class="group flex items-center rounded-lg px-4 py-2.5 transition {{ $item['active'] ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
              :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
            >
              <span x-show="!sidebarCollapsed" x-cloak class="flex min-w-0 flex-1 items-center justify-between gap-3">
                <span class="truncate">{{ $item['label'] }}</span>
                @if (($item['badge'] ?? 0) > 0)
                  <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-blue-600 px-2 py-0.5 text-[11px] font-semibold text-white {{ $item['active'] ? '' : 'badge-pulse-soft' }}">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                  </span>
                @endif
              </span>
              <span x-show="sidebarCollapsed" x-cloak class="relative mx-auto inline-flex items-center justify-center">
                <x-nav-icon :name="$item['icon']" class="h-5 w-5" />
                @if (($item['badge'] ?? 0) > 0)
                  <span class="badge-pulse-soft absolute -right-2 -top-2 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-semibold text-white">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                  </span>
                @endif
              </span>
            </a>
          @endforeach
        </nav>

        <div class="border-t border-slate-200 p-3 space-y-2">
          @if (auth()->user()->isEmployee())
            <a
              href="{{ route('tickets.create') }}"
              class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
              :title="sidebarCollapsed ? 'Report Issue' : ''"
            >
              <span x-show="!sidebarCollapsed" x-cloak>Report Issue</span>
              <span x-show="sidebarCollapsed" x-cloak>+</span>
            </a>
          @endif
          <button
            type="button"
            @click="toggleSidebar"
            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
            :aria-label="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
            :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
          >
            <span x-show="!sidebarCollapsed" x-cloak>Collapse Sidebar</span>
            <span x-show="sidebarCollapsed" x-cloak>Expand</span>
          </button>
        </div>
      </aside>
    @endauth

    <div class="flex min-h-screen min-w-0 flex-1 flex-col">
      <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4">
          @guest
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-semibold text-slate-800">
              <img src="{{ asset('images/cwl1-logo.jpeg') }}" alt="CWL1 logo" class="h-8 w-8 rounded-lg object-cover" />
              <span>Site Bulletin</span>
            </a>
          @else
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-lg font-semibold text-slate-800 lg:hidden">
              <img src="{{ asset('images/cwl1-logo.jpeg') }}" alt="CWL1 logo" class="h-8 w-8 rounded-lg object-cover" />
              <span>Site Bulletin</span>
            </a>
            <a href="{{ route('home') }}" x-show="scrolled" x-transition.opacity class="hidden lg:inline-flex lg:items-center lg:gap-3">
              <img src="{{ asset('images/cwl1-logo.jpeg') }}" alt="CWL1 logo" class="h-10 w-10 rounded-xl object-cover ring-1 ring-slate-200" />
              <div>
                <p class="text-base font-semibold text-slate-900">Site Bulletin</p>
                <p class="text-sm text-slate-500">Operations Portal</p>
              </div>
            </a>
          @endguest
          <nav class="ml-auto flex items-center gap-4 text-sm font-medium">
            @auth
              <div class="relative inline-block">
                <x-notification-bell />
              </div>
              <x-dropdown align="right" width="72" contentClasses="overflow-hidden rounded-2xl bg-white py-2">
                <x-slot name="trigger">
                  <button type="button" class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-2 py-2 text-left text-slate-700 transition hover:border-slate-300 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-sm font-semibold text-white">
                      {{ $userInitials }}
                    </span>
                    <span class="hidden min-w-0 lg:block">
                      <span class="block truncate text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                      <span class="block truncate text-xs text-slate-500">{{ $userRoleLabel }}</span>
                    </span>
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </x-slot>

                <x-slot name="content">
                  <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                      <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">
                        {{ $userInitials }}
                      </span>
                      <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="px-2 py-2">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                      <svg class="h-5 w-5 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 2a4 4 0 100 8 4 4 0 000-8zM3 16a7 7 0 1114 0v1H3v-1z" />
                      </svg>
                      <span>Profile</span>
                    </a>
                    <a href="{{ route('profile.edit') }}#personal" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                      <svg class="h-5 w-5 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.25-8.75a.75.75 0 00-1.5 0v2.19L9.53 9.22a.75.75 0 10-1.06 1.06l2.22 2.22H8.5a.75.75 0 000 1.5h4a.75.75 0 00.75-.75v-4z" clip-rule="evenodd" />
                      </svg>
                      <span>Notification Preferences</span>
                    </a>
                  </div>

                  <div class="border-t border-slate-100 px-2 py-2">
                    <form action="{{ route('logout') }}" method="POST">
                      @csrf
                      <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                        <svg class="h-5 w-5 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                          <path fill-rule="evenodd" d="M3 4.75A1.75 1.75 0 014.75 3h5.5a.75.75 0 010 1.5h-5.5a.25.25 0 00-.25.25v10.5c0 .138.112.25.25.25h5.5a.75.75 0 010 1.5h-5.5A1.75 1.75 0 013 15.25V4.75zm10.22 2.47a.75.75 0 011.06 0l3.25 3.25a.75.75 0 010 1.06l-3.25 3.25a.75.75 0 01-1.06-1.06l1.97-1.97H8.75a.75.75 0 010-1.5h6.44l-1.97-1.97a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                        <span>Log Out</span>
                      </button>
                    </form>
                  </div>
                </x-slot>
              </x-dropdown>
            @else
              <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">Log in</a>
              @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-100">Register</a>
              @endif
            @endauth
          </nav>
        </div>
      </header>

      <main class="flex-1 w-full min-w-0 overflow-x-hidden">
        <div class="mx-auto w-full min-w-0 max-w-7xl px-4 py-6 pb-24 md:pb-6">
          @if (session('status'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
              {{ session('status') }}
            </div>
          @endif

          @yield('content')
        </div>
      </main>

      <footer class="border-t border-slate-200 bg-white/90 py-4 text-center text-xs text-slate-500 backdrop-blur">
        &copy; {{ now()->year }} Site Bulletin.@if ($showPrototypeFooter ?? false) Coursework prototype.@endif
      </footer>
    </div>
  </div>
  @auth
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur">
      <div class="grid grid-cols-5 gap-1 px-2 py-2">
        @foreach ($mobileNavItems as $item)
          <a
            href="{{ $item['route'] }}"
            @if ($item['active']) aria-current="page" @endif
            class="rounded-xl px-1 py-1.5 text-[10px] tracking-tight transition {{ $item['active'] ? 'text-blue-700' : 'text-slate-500 hover:bg-slate-100/90 hover:text-slate-800' }}"
          >
            <span class="mx-auto flex w-full flex-col items-center gap-1">
              <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-2xl transition {{ $item['active'] ? 'bg-blue-600 text-white shadow-sm ring-4 ring-blue-100' : 'bg-slate-100 text-slate-500' }}">
                <x-nav-icon :name="$item['icon']" class="{{ $item['active'] ? 'h-6 w-6' : 'h-5 w-5' }}" />
                @if (($item['badge'] ?? 0) > 0)
                  <span class="badge-pulse-soft absolute -right-1.5 -top-1.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-semibold text-white shadow-sm">
                    {{ $item['badge'] > 9 ? '9+' : $item['badge'] }}
                  </span>
                @endif
              </span>
              <span class="max-w-full truncate leading-none {{ $item['active'] ? 'font-semibold text-blue-700' : 'font-medium text-slate-500' }}">{{ $item['label'] }}</span>
            </span>
          </a>
        @endforeach
      </div>
    </nav>
  @endauth
</body>
</html>
