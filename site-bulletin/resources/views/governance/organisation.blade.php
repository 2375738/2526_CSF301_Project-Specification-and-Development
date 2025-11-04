@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Organisation Structure</h1>
      <p class="mt-2 text-sm text-slate-600">
        Current department hierarchy with manager assignments and member counts.
      </p>
    </header>

    <section class="space-y-4">
      @foreach ($departments as $department)
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">{{ $department->name }}</h2>
              @if ($department->description)
                <p class="mt-1 text-sm text-slate-600">{{ $department->description }}</p>
              @endif
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-0.5 text-xs font-semibold uppercase text-slate-600">
              {{ $department->members->count() }} Members
            </span>
          </header>

          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <section>
              <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Managers</h3>
              <ul class="mt-2 space-y-1 text-sm text-slate-700">
                @forelse ($department->managers as $manager)
                  <li>{{ $manager->name }}</li>
                @empty
                  <li class="text-xs text-slate-500">No manager assigned.</li>
                @endforelse
              </ul>
            </section>

            <section>
              <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Members</h3>
              <div class="mt-2 flex flex-wrap gap-2">
                @forelse ($department->members as $member)
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                    {{ $member->name }}
                  </span>
                @empty
                  <span class="text-xs text-slate-500">No members linked yet.</span>
                @endforelse
              </div>
            </section>
          </div>
        </article>
      @endforeach
    </section>
  </div>
@endsection
