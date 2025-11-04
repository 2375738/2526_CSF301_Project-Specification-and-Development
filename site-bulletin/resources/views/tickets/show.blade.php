@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="bg-white shadow-sm rounded-xl px-6 py-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <span>#{{ $ticket->id }}</span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">
              {{ ucfirst(str_replace('_', ' ', $ticket->status->value ?? $ticket->status)) }}
            </span>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-700">
              {{ ucfirst($ticket->priority->value ?? $ticket->priority) }}
            </span>
          </div>
          <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $ticket->title }}</h1>
          <div class="mt-2 text-sm text-slate-600 space-y-1">
            <p>Category: <span class="font-medium text-slate-800">{{ $ticket->category?->name ?? 'Uncategorised' }}</span></p>
            @if ($ticket->location)
              <p>Location: <span class="font-medium text-slate-800">{{ $ticket->location }}</span></p>
            @endif
            <p>Opened by {{ $ticket->requester->name }} on {{ $ticket->created_at->format('M j, Y H:i') }}</p>
            @if ($ticket->createdFor && $ticket->created_for_id !== $ticket->requester_id)
              <p>Raised for <span class="font-medium text-slate-800">{{ $ticket->createdFor->name }}</span></p>
            @endif
            @if ($ticket->department)
              <p>Department: <span class="font-medium text-slate-800">{{ $ticket->department->name }}</span></p>
            @endif
           @if ($ticket->assignee)
             <p>Assigned to {{ $ticket->assignee->name }}</p>
           @endif
            @if (($ticket->sla_first_response_breached ?? false) || ($ticket->sla_resolution_breached ?? false))
              <p class="mt-1 text-sm font-semibold text-rose-600">SLA warning: attention required</p>
            @endif
            @if ($ticket->duplicateOf)
              <p class="text-amber-700 font-medium">
                Duplicate of
                <a href="{{ route('tickets.show', $ticket->duplicateOf) }}" class="underline">#{{ $ticket->duplicateOf->id }}</a>.
              </p>
            @endif
          </div>
        </div>
        <div class="text-sm text-slate-600">
          <p>Last updated {{ $ticket->updated_at->diffForHumans() }}</p>
          @if ($ticket->closed_at)
            <p>Closed on {{ $ticket->closed_at->format('M j, Y H:i') }}</p>
          @endif
        </div>
      </div>

      <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        {!! nl2br(e($ticket->description)) !!}
      </div>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
      <div class="md:col-span-2 space-y-6">
        <section class="bg-white shadow-sm rounded-xl px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Service Targets</h2>
          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 px-4 py-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">First response</p>
              <p class="mt-1 text-sm text-slate-700">
                {{ $sla['first_response_minutes'] ?? '—' }} mins
                <span class="text-xs text-slate-500">(target {{ $sla['targets']['first_response_minutes'] }} mins)</span>
              </p>
              @if ($sla['first_response_breached'])
                <span class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700">Breached</span>
              @endif
            </div>
            <div class="rounded-lg border border-slate-200 px-4 py-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">Resolution active time</p>
              <p class="mt-1 text-sm text-slate-700">
                {{ $sla['resolution_active_minutes'] }} mins
                <span class="text-xs text-slate-500">(target {{ $sla['targets']['resolution_minutes'] }} mins)</span>
              </p>
              @if ($sla['resolution_breached'])
                <span class="mt-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700">Breached</span>
              @endif
            </div>
          </div>
        </section>

        <section class="bg-white shadow-sm rounded-xl px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">Status Timeline</h2>
          <ul class="mt-4 space-y-4">
            @forelse ($ticket->statusChanges as $change)
              <li class="relative border-l-2 border-slate-200 pl-4">
                <div class="absolute -left-1.5 top-1 h-3 w-3 rounded-full bg-blue-500"></div>
                <p class="text-sm font-semibold text-slate-800">
                  {{ ucfirst(str_replace('_', ' ', $change->to_status->value ?? $change->to_status)) }}
                  @if ($change->user)
                    <span class="font-normal text-slate-500">by {{ $change->user->name }}</span>
                  @endif
                </p>
                <p class="text-xs text-slate-500">{{ $change->created_at->format('M j, Y H:i') }}</p>
                @if ($change->reason)
                  <p class="mt-1 text-sm text-slate-600">{{ $change->reason }}</p>
                @endif
              </li>
            @empty
              <li class="text-sm text-slate-500">No status history yet.</li>
            @endforelse
          </ul>
        </section>

        <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Activity &amp; Updates</h2>
            <span class="text-xs text-slate-500">{{ $comments->count() }} notes</span>
          </div>

          <ul class="space-y-4">
            @forelse ($comments as $comment)
              <li class="rounded-lg border border-slate-200 px-4 py-3">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold text-slate-800">
                    {{ $comment->author->name }}
                    @if ($comment->is_private)
                      <span class="ml-2 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-600">Private</span>
                    @endif
                  </p>
                  <p class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</p>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $comment->body }}</p>
              </li>
            @empty
              <li class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                No updates yet. Add a comment below to kick things off.
              </li>
            @endforelse
          </ul>

          @can('comment', $ticket)
            <form action="{{ route('tickets.comments.store', $ticket) }}" method="POST" class="space-y-3">
              @csrf
              <label class="block text-sm font-medium text-slate-700">
                Add an update
                <textarea name="body" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" required>{{ old('body') }}</textarea>
              </label>
              @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                  <input type="checkbox" name="is_private" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                  Private note (requester cannot see)
                </label>
              @endif
              <button type="submit" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Post Comment
              </button>
            </form>
          @endcan
        </section>
      </div>

      <div class="space-y-6">
        <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-3">
          <h2 class="text-lg font-semibold text-slate-900">SLA Snapshot</h2>
          <div class="rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-700 space-y-2">
            <p>
              First response: <span class="font-semibold">{{ $sla['first_response_minutes'] !== null ? $sla['first_response_minutes'].' mins' : 'Pending' }}</span>
              <br />
              Target: {{ $sla['targets']['first_response_minutes'] }} mins
              @if ($sla['first_response_breached'])
                <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-600">Breached</span>
              @endif
            </p>
            <p>
              Resolution active time: <span class="font-semibold">{{ $sla['resolution_active_minutes'] }} mins</span>
              <br />
              Target: {{ $sla['targets']['resolution_minutes'] }} mins
              @if ($sla['resolution_breached'])
                <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-600">Breached</span>
              @endif
            </p>
          </div>
        </section>

        <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-3">
          <h2 class="text-lg font-semibold text-slate-900">Attachments</h2>
          <ul class="space-y-2 text-sm text-slate-700">
            @forelse ($ticket->attachments as $attachment)
              <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                <div>
                  <p class="font-medium text-slate-800">{{ $attachment->original_name }}</p>
                  <p class="text-xs text-slate-500">
                    Uploaded by {{ $attachment->uploader->name }} · {{ number_format(($attachment->size ?? 0) / 1024, 1) }} KB
                  </p>
                </div>
                <a href="{{ $attachment->download_url }}" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-300">
                  Download
                </a>
              </li>
            @empty
              <li class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-center text-sm text-slate-500">
                No attachments yet.
              </li>
            @endforelse
          </ul>

          @can('upload', $ticket)
            <form action="{{ route('tickets.attachments.store', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-2 text-sm">
              @csrf
              <input type="file" name="attachment" required class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" accept="image/jpeg,image/png,application/pdf" />
              <button type="submit" class="inline-flex items-center rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-white shadow-sm hover:bg-slate-900">
                Upload Attachment
              </button>
            </form>
          @endcan
        </section>

        @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
          <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Triage &amp; Assign</h2>
            <form action="{{ route('tickets.status.update', $ticket) }}" method="POST" class="space-y-3 text-sm text-slate-700">
              @csrf
              @method('PATCH')
              <label class="block font-medium">
                Status
                <select name="status" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  @foreach ($statusOptions as $status)
                    <option value="{{ $status->value }}" @selected(($ticket->status->value ?? $ticket->status) === $status->value)>{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                  @endforeach
                </select>
              </label>

              <label class="block font-medium">
                Priority
                <select name="priority" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  @foreach ($priorityOptions as $priority)
                    <option value="{{ $priority->value }}" @selected(($ticket->priority->value ?? $ticket->priority) === $priority->value)>{{ ucfirst($priority->value) }}</option>
                  @endforeach
                </select>
              </label>

              <label class="block font-medium">
                Assignee
                <select name="assignee_id" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                  <option value="">Unassigned</option>
                  @foreach ($assignableUsers as $user)
                    <option value="{{ $user->id }}" @selected($ticket->assignee_id === $user->id)>
                      {{ $user->name }}
                      (
                        {{ $user->role instanceof \App\Enums\UserRole ? $user->role->label() : ucfirst($user->role) }}
                      )
                    </option>
                  @endforeach
                </select>
              </label>

              <label class="block font-medium">
                Duplicate of (ticket ID)
                <input type="number" name="duplicate_of_id" value="{{ old('duplicate_of_id', $ticket->duplicate_of_id) }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ticket ID" />
              </label>

              <label class="block font-medium">
                Comment / note
                <textarea name="comment" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('comment') }}</textarea>
              </label>

              <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="is_private" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Save as private manager note
              </label>

              <button type="submit" class="inline-flex items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Update Ticket
              </button>
            </form>
          </section>
        @elseif (auth()->id() === $ticket->requester_id)
          <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-3">
            <h2 class="text-lg font-semibold text-slate-900">Requester Actions</h2>
            <p class="text-sm text-slate-600">
              Confirm once the fix works for you, or reopen if the issue is still present.
            </p>

            @if (in_array($ticket->status->value ?? $ticket->status, ['resolved']))
              <form action="{{ route('tickets.status.update', $ticket) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="closed">
                <button type="submit" class="inline-flex items-center rounded-full bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                  Confirm &amp; Close
                </button>
              </form>
            @endif

            @if (in_array($ticket->status->value ?? $ticket->status, ['resolved', 'closed']))
              <form action="{{ route('tickets.status.update', $ticket) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="reopened">
                <button type="submit" class="ml-3 inline-flex items-center rounded-full bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                  Reopen Ticket
                </button>
              </form>
            @endif
          </section>
        @endif
      </div>
    </div>
  </div>
@endsection
