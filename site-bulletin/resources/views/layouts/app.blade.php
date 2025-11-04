<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ config('app.name', 'Site Bulletin') }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-slate-50 text-gray-900">
  <div class="min-h-screen flex flex-col">
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="text-lg font-semibold text-slate-800">Site Bulletin</a>
        <nav class="flex items-center gap-4 text-sm font-medium">
          <a href="{{ route('home') }}" class="text-slate-600 hover:text-slate-900">Dashboard</a>
          @auth
            <a href="{{ route('tickets.index') }}" class="text-slate-600 hover:text-slate-900">My Tickets</a>
            @if (auth()->user()->hasRole('manager','ops_manager','hr','admin'))
              <a href="{{ route('governance.index') }}" class="text-slate-600 hover:text-slate-900">Governance</a>
            @endif
            <a href="{{ route('profile.edit') }}" class="text-slate-600 hover:text-slate-900">Profile</a>
            @if (auth()->user()->isEmployee())
              <a href="{{ route('tickets.create') }}" class="inline-flex items-center rounded-full bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">
                Report Issue
              </a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="inline">
              @csrf
              <button type="submit" class="text-slate-600 hover:text-slate-900">Logout</button>
            </form>
          @else
            <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Log in</a>
            @if (Route::has('register'))
              <a href="{{ route('register') }}" class="text-slate-600 hover:text-slate-900">Register</a>
            @endif
          @endauth
        </nav>
      </div>
    </header>
    <main class="flex-1 w-full">
      <div class="max-w-7xl mx-auto w-full px-4 py-6">
        @if (session('status'))
          <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            {{ session('status') }}
          </div>
        @endif

        @yield('content')
      </div>
    </main>
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
      &copy; {{ now()->year }} Site Bulletin. Coursework prototype.
    </footer>
  </div>
</body>
</html>
