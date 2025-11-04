@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Account</p>
        <h1 class="text-2xl font-semibold text-slate-900">Profile Settings</h1>
        <p class="text-sm text-slate-600">Update your contact information, password, or remove your account.</p>
      </div>
      <a href="{{ route('home') }}" class="text-sm font-medium text-blue-600 hover:underline">Back to Dashboard</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="p-6 bg-white shadow-sm rounded-xl border border-slate-100">
        @include('profile.partials.update-profile-information-form')
      </div>

      <div class="space-y-6">
        <div class="p-6 bg-white shadow-sm rounded-xl border border-slate-100">
          @include('profile.partials.update-password-form')
        </div>
        <div class="p-6 bg-white shadow-sm rounded-xl border border-slate-100">
          @include('profile.partials.delete-user-form')
        </div>
      </div>
    </div>
  </div>
@endsection
