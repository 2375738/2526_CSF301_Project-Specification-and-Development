@extends('layouts.app')

@section('content')
  <div class="max-w-3xl mx-auto space-y-6">
    <div class="bg-white shadow-sm rounded-xl px-6 py-5">
      <h1 class="text-xl font-semibold text-slate-900">Report an Issue</h1>
      <p class="mt-2 text-sm text-slate-600">
        Provide as much detail as possible so the site team can respond quickly. Attach a photo or PDF if it helps explain the problem.
      </p>
    </div>

    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-xl px-6 py-6 space-y-5">
      @csrf

      @if ($canActOnBehalf)
        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm font-medium text-slate-700">
            Employee
            <select name="created_for_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">Select employee...</option>
              @foreach ($employeeOptions as $employee)
                <option value="{{ $employee->id }}" @selected(old('created_for_id') == $employee->id)>{{ $employee->name }}</option>
              @endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">Choose who this ticket is for. Leave blank to keep it assigned to you.</span>
            @error('created_for_id')
              <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
            @enderror
          </label>

          <label class="block text-sm font-medium text-slate-700">
            Department (optional)
            <select name="department_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
              <option value="">Auto-select from employee</option>
              @foreach ($departmentOptions as $department)
                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
              @endforeach
            </select>
            @error('department_id')
              <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>
            @enderror
          </label>
        </div>
      @endif

      <div class="grid gap-4 md:grid-cols-2">
        <label class="block text-sm font-medium text-slate-700">
          Category
          <select name="category_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">Select category...</option>
            @foreach ($categories as $category)
              <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                {{ $category->name }} @if ($category->is_sensitive) (HR Restricted) @endif
              </option>
            @endforeach
          </select>
        </label>

        <label class="block text-sm font-medium text-slate-700">
          Location (optional)
          <input type="text" name="location" value="{{ old('location') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Inbound Dock A3" />
        </label>
      </div>

      <label class="block text-sm font-medium text-slate-700">
        Title
        <input type="text" name="title" value="{{ old('title') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Short summary" required />
      </label>

      <label class="block text-sm font-medium text-slate-700">
        Description
        <textarea name="description" rows="5" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Explain what happened, the impact, and any immediate actions taken." required>{{ old('description') }}</textarea>
      </label>

      <label class="block text-sm font-medium text-slate-700">
        Attachment (optional)
        <input type="file" name="attachment" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" accept="image/jpeg,image/png,application/pdf" />
        <span class="mt-1 block text-xs text-slate-500">JPEG, PNG or PDF up to 10MB.</span>
      </label>

      @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          <p class="font-semibold">Please correct the following:</p>
          <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="flex items-center justify-end gap-3">
        <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        <button type="submit" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
          Submit Ticket
        </button>
      </div>
    </form>

    <div class="bg-white shadow-sm rounded-xl px-6 py-5">
      <h2 class="text-base font-semibold text-slate-900">Possible duplicates</h2>
      <p class="mt-1 text-xs text-slate-500">
        If one of these matches your issue, link to it instead so the team can keep updates together.
      </p>

      @if ($similarTickets->isEmpty())
        <p class="mt-4 text-sm text-slate-500">No similar open tickets right now.</p>
      @else
        <ul class="mt-4 space-y-3">
          @foreach ($similarTickets as $similar)
            <li class="flex flex-col gap-2 rounded-lg border border-slate-200 px-4 py-3">
              <div>
                <p class="text-sm font-semibold text-slate-800">
                  #{{ $similar->id }} · {{ $similar->title }}
                </p>
                <p class="text-xs text-slate-500">
                  Status: {{ ucfirst(str_replace('_', ' ', $similar->status->value ?? $similar->status)) }}
                </p>
              </div>
              <form action="{{ route('tickets.link') }}" method="POST" class="flex items-center gap-3 text-sm">
                @csrf
                <input type="hidden" name="ticket_id" value="{{ $similar->id }}">
                <input type="text" name="message" placeholder="Add a note (optional)" class="flex-1 rounded-lg border border-slate-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="submit" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 font-medium text-slate-700 hover:bg-slate-300">
                  Link me to this ticket
                </button>
              </form>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
@endsection
