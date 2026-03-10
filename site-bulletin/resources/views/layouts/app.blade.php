<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'Site Bulletin') }}</title>
  <style>[x-cloak] { display: none !important; }</style>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="cwl1-bg cwl1-noise text-gray-900">
  @php
    $showGovernance = auth()->check() && auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin');
    $desktopNavItems = [
      ['label' => 'Dashboard', 'route' => route('home'), 'active' => request()->routeIs('home', 'dashboard')],
      ['label' => 'Announcements', 'route' => route('announcements.index'), 'active' => request()->routeIs('announcements.*')],
      ['label' => 'Messages', 'route' => route('messages.index'), 'active' => request()->routeIs('messages.*')],
      ['label' => 'Knowledge', 'route' => route('knowledge.index'), 'active' => request()->routeIs('knowledge.*')],
      ['label' => 'Tasks', 'route' => route('tickets.index'), 'active' => request()->routeIs('tickets.*')],
      ['label' => 'Profile', 'route' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
    ];

    if ($showGovernance) {
      $desktopNavItems[] = ['label' => 'Governance', 'route' => route('governance.index'), 'active' => request()->routeIs('governance.*')];
    }

    $mobileNavItems = [
      ['label' => 'Dashboard', 'route' => route('home'), 'active' => request()->routeIs('home', 'dashboard')],
      ['label' => 'Announcements', 'route' => route('announcements.index'), 'active' => request()->routeIs('announcements.*')],
      ['label' => 'Messages', 'route' => route('messages.index'), 'active' => request()->routeIs('messages.*')],
      ['label' => 'Knowledge', 'route' => route('knowledge.index'), 'active' => request()->routeIs('knowledge.*')],
      ['label' => 'Tasks', 'route' => route('tickets.index'), 'active' => request()->routeIs('tickets.*')],
      ['label' => 'Profile', 'route' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
    ];
  @endphp
  <div
    class="min-h-screen lg:flex"
    x-data="{
      sidebarCollapsed: localStorage.getItem('site-bulletin-sidebar-collapsed') === '1',
      toggleSidebar() {
        this.sidebarCollapsed = !this.sidebarCollapsed;
        localStorage.setItem('site-bulletin-sidebar-collapsed', this.sidebarCollapsed ? '1' : '0');
      }
    }"
  >
    @auth
      <aside
        class="hidden lg:flex lg:flex-col border-r border-slate-200 bg-white transition-all duration-200"
        :class="sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'"
      >
        <div class="border-b border-slate-200 px-4 py-4">
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
              class="group flex items-center gap-3 rounded-lg px-3 py-2 transition {{ $item['active'] ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
              :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
            >
              <span class="inline-flex h-2 w-2 rounded-full {{ $item['active'] ? 'bg-white' : 'bg-slate-400 group-hover:bg-slate-700' }}"></span>
              <span x-show="!sidebarCollapsed" x-cloak>{{ $item['label'] }}</span>
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

    <div class="flex min-h-screen flex-1 flex-col">
      <header @class([
        'bg-white/90 shadow-sm backdrop-blur',
        'sticky top-0 z-50' => auth()->guest(),
      ])>
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
            <div class="hidden lg:block">
              <p class="text-sm text-slate-500">Operations Portal</p>
            </div>
          @endguest
          <nav class="flex items-center gap-4 text-sm font-medium">
            @auth
              <div class="relative inline-block">
                <x-notification-bell />
              </div>
              <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-slate-600 hover:text-slate-900">Logout</button>
              </form>
            @else
              <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-white hover:bg-slate-700">Log in</a>
              @if (Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-full border border-slate-300 px-4 py-2 text-slate-700 hover:bg-slate-100">Register</a>
              @endif
            @endauth
          </nav>
        </div>
      </header>

      <main class="flex-1 w-full">
        <div class="mx-auto w-full max-w-7xl px-4 py-6 pb-24 md:pb-6">
          @if (session('status'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
              {{ session('status') }}
            </div>
          @endif

          @yield('content')
        </div>
      </main>

      <footer class="border-t border-slate-200 bg-white/90 py-4 text-center text-xs text-slate-500 backdrop-blur">
        &copy; {{ now()->year }} Site Bulletin. Coursework prototype.
      </footer>
    </div>
  </div>
  @auth
    <nav class="md:hidden fixed bottom-0 inset-x-0 border-t border-slate-200 bg-white z-40">
      <div class="grid grid-cols-5 gap-1 px-2 py-2">
        @foreach ($mobileNavItems as $item)
          <a
            href="{{ $item['route'] }}"
            class="text-center rounded-lg px-2 py-2 text-xs font-medium {{ $item['active'] ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}"
          >
            {{ $item['label'] }}
          </a>
        @endforeach
      </div>
    </nav>
  @endauth
</body>
</html>
