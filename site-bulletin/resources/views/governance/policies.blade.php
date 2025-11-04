@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Policies & Procedures</h1>
      <p class="mt-2 text-sm text-slate-600">
        Key documents that guide health, safety, and leadership expectations across the site.
      </p>
    </header>

    <section class="space-y-4">
      @foreach ($policies as $policy)
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">{{ $policy['title'] }}</h2>
          <p class="mt-2 text-sm text-slate-600">{{ $policy['summary'] }}</p>
          <div class="mt-4 flex items-center gap-3 text-xs text-slate-500">
            <span>Last reviewed: {{ now()->subMonths(rand(0, 3))->format('M Y') }}</span>
            <span>&middot;</span>
            <a href="#" class="font-medium text-blue-600 hover:underline">Download PDF</a>
            <a href="#" class="font-medium text-blue-600 hover:underline">View revision history</a>
          </div>
        </article>
      @endforeach
    </section>
  </div>
@endsection
