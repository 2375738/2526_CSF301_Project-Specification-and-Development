@extends('layouts.app')

@section('content')
  <div class="max-w-3xl space-y-6">
    <header class="rounded-xl border border-slate-200 bg-white px-6 py-5 shadow-sm">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Governance</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-900">Request Role Change</h1>
      <p class="mt-2 text-sm text-slate-600">
        Submit a change for yourself or a team member. HR will review and confirm via audit log.
      </p>
    </header>

    <section class="rounded-xl border border-slate-200 bg-white px-6 py-6 shadow-sm">
      <form action="{{ route('role-requests.store') }}" method="POST" class="space-y-4">
        @csrf

        <label class="block text-sm font-medium text-slate-700">
          Target colleague
          <select name="target_user_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select user…</option>
            @foreach ($targets as $id => $name)
              <option value="{{ $id }}" @selected(old('target_user_id') == $id)>{{ $name }}</option>
            @endforeach
          </select>
          @error('target_user_id')
            <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
          @enderror
        </label>

        <label class="block text-sm font-medium text-slate-700">
          Requested role
          <select name="requested_role" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select role…</option>
            @foreach ($roles as $role)
              <option value="{{ $role['value'] }}" @selected(old('requested_role') === $role['value'])>
                {{ $role['label'] }}
              </option>
            @endforeach
          </select>
          @error('requested_role')
            <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
          @enderror
        </label>

        <label class="block text-sm font-medium text-slate-700">
          Department (optional)
          <select name="department_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Select department…</option>
            @foreach ($departments as $id => $name)
              <option value="{{ $id }}" @selected(old('department_id') == $id)>{{ $name }}</option>
            @endforeach
          </select>
          @error('department_id')
            <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
          @enderror
        </label>

        <label class="block text-sm font-medium text-slate-700">
          Justification (optional)
          <textarea name="justification" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('justification') }}</textarea>
          @error('justification')
            <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
          @enderror
        </label>

        <div class="flex items-center justify-end gap-3">
          <a href="{{ route('role-requests.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">
            Cancel
          </a>
          <button type="submit"
                  class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Submit for Review
          </button>
        </div>
      </form>
    </section>
  </div>
@endsection
