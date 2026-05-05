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
            @if ($templateMeta)
              <p>Template: <span class="font-medium text-slate-800">{{ $templateMeta['label'] ?? $ticket->template_key }}</span></p>
            @endif
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

      @if (! empty($ticket->details_json))
        <div class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-4">
          <h2 class="text-sm font-semibold text-slate-900">Structured Details</h2>
          <dl class="mt-3 grid gap-3 md:grid-cols-2">
            @foreach ($ticket->details_json as $detail)
              @if (! empty($detail['label']) && ! empty($detail['value']))
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                  <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $detail['label'] }}</dt>
                  <dd class="mt-1 text-sm text-slate-800">{{ $detail['value'] }}</dd>
                </div>
              @endif
            @endforeach
          </dl>
        </div>
      @endif
    </div>

    <div class="grid gap-6 md:grid-cols-3">
      <div class="md:col-span-2 space-y-6">
        <section class="bg-white shadow-sm rounded-xl px-6 py-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">What This Means</h2>
              <p class="mt-1 text-sm text-slate-600">{{ $lifecycle['headline'] }}</p>
            </div>
            <span @class([
              'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide',
              'bg-amber-100 text-amber-700' => $lifecycle['isRequesterActionRequired'],
              'bg-blue-100 text-blue-700' => ! $lifecycle['isRequesterActionRequired'],
            ])>
              {{ $lifecycle['statusLabel'] }}
            </span>
          </div>

          <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 px-4 py-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">Who is handling this</p>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ $lifecycle['ownerLabel'] }}</p>
              <p class="mt-2 text-sm text-slate-600">{{ $lifecycle['ownerDetail'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 px-4 py-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">What happens next</p>
              <p class="mt-1 text-sm text-slate-700">{{ $lifecycle['nextStep'] }}</p>
            </div>
          </div>

          <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
              <p class="text-xs uppercase tracking-wide text-slate-500">Latest visible update</p>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ $lifecycle['latestVisibleUpdate'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
              <p class="text-xs uppercase tracking-wide text-slate-500">Service note</p>
              <p class="mt-1 text-sm text-slate-700">{{ $lifecycle['serviceNote'] }}</p>
            </div>
          </div>

          @if ($lifecycle['requesterActionLabel'])
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">{{ $lifecycle['requesterActionLabel'] }}</p>
              <p class="mt-1 text-sm text-amber-900">{{ $lifecycle['requesterActionDetail'] }}</p>
            </div>
          @endif
        </section>

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

        @if (($approvalSummary ?? collect())->isNotEmpty())
          <section class="bg-white shadow-sm rounded-xl px-6 py-5 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Approval Status</h2>
              <p class="mt-1 text-sm text-slate-600">Track the approval path for request-based tickets such as missed punch corrections and shift swaps.</p>
            </div>

            @if (($approvalContext['requester_status'] ?? null) || ($approvalContext['requester_note'] ?? null))
              <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-4">
                @if ($approvalContext['requester_status'] ?? null)
                  <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Approval progress</p>
                  <p class="mt-1 text-sm font-semibold text-blue-900">{{ $approvalContext['requester_status'] }}</p>
                @endif
                @if ($approvalContext['requester_note'] ?? null)
                  <p class="mt-2 text-sm text-blue-900">{{ $approvalContext['requester_note'] }}</p>
                @endif
              </div>
            @endif

            @foreach ($approvalSummary as $approval)
              <div class="rounded-lg border border-slate-200 px-4 py-4 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Step {{ $approval['step_order'] }} · {{ str_replace('_', ' ', $approval['step_key']) }}</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $approval['status_label'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                      Reviewer group: {{ ucfirst($approval['approver_role']) }}
                      @if ($approval['approver_name'])
                        · Decision by {{ $approval['approver_name'] }}
                      @endif
                    </p>
                  </div>
                  @if ($approval['decided_at'])
                    <span class="text-xs text-slate-500">{{ $approval['decided_at']->diffForHumans() }}</span>
                  @endif
                </div>

                @if ($approval['public_note'])
                  <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                    {{ $approval['public_note'] }}
                  </div>
                @endif

                @if ($approval['status'] === 'queued')
                  <p class="text-xs text-slate-500">This step becomes active only after the previous approval step is completed.</p>
                @endif

                @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin') && $approval['internal_note'])
                  <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-900">
                    <p class="font-semibold">Internal note</p>
                    <p class="mt-1">{{ $approval['internal_note'] }}</p>
                  </div>
                @endif

                @php
                  $canApprove = false;

                  if ($approval['approver_role'] === 'hr') {
                      $canApprove = auth()->user()->hasRole('hr', 'admin');
                  } elseif ($approval['approver_role'] === 'manager') {
                      $managedDepartmentIds = auth()->user()->managedDepartments()->pluck('departments.id');
                      $canApprove = auth()->user()->hasRole('hr', 'admin')
                          || (
                              auth()->user()->hasRole('manager', 'ops_manager')
                              && $ticket->department_id
                              && $managedDepartmentIds->contains($ticket->department_id)
                          );
                  }
                @endphp

                @if ($approval['status'] === 'pending' && $canApprove)
                  <form action="{{ route('tickets.approvals.update', [$ticket, $approval['id']]) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div class="grid gap-3 md:grid-cols-2">
                      <label class="block text-sm font-medium text-slate-700">
                        Public note
                        <textarea name="public_note" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                      </label>
                      <label class="block text-sm font-medium text-slate-700">
                        Internal note
                        <textarea name="internal_note" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                      </label>
                    </div>
                    <div class="flex flex-wrap gap-2">
                      <button type="submit" name="decision" value="approved" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        Approve
                      </button>
                      <button type="submit" name="decision" value="needs_info" class="inline-flex items-center rounded-full bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                        Need More Info
                      </button>
                      <button type="submit" name="decision" value="rejected" class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        Reject
                      </button>
                    </div>
                  </form>
                @endif
              </div>
            @endforeach
          </section>

          @if (($approvalContext['history'] ?? collect())->isNotEmpty())
            <section class="bg-white shadow-sm rounded-xl px-6 py-5">
              <h2 class="text-lg font-semibold text-slate-900">Approval History</h2>
              <ul class="mt-4 space-y-4">
                @foreach ($approvalContext['history'] as $approval)
                  <li class="relative border-l-2 border-slate-200 pl-4">
                    <div class="absolute -left-1.5 top-1 h-3 w-3 rounded-full bg-emerald-500"></div>
                    <p class="text-sm font-semibold text-slate-800">
                      Step {{ $approval->step_order }} · {{ str_replace('_', ' ', $approval->step_key) }}
                      <span class="font-normal text-slate-500">· {{ $approval->publicStatusLabel() }}</span>
                    </p>
                    <p class="text-xs text-slate-500">
                      {{ ucfirst($approval->approver_role) }}
                      @if ($approval->approver?->name)
                        · {{ $approval->approver->name }}
                      @endif
                      @if ($approval->decided_at)
                        · {{ $approval->decided_at->format('M j, Y H:i') }}
                      @endif
                    </p>
                    @if ($approval->public_note)
                      <p class="mt-1 text-sm text-slate-600">{{ $approval->public_note }}</p>
                    @endif
                    @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin') && $approval->internal_note)
                      <p class="mt-1 text-sm text-amber-800">Internal note: {{ $approval->internal_note }}</p>
                    @endif
                  </li>
                @endforeach
              </ul>
            </section>
          @endif
        @endif

        <section class="bg-white shadow-sm rounded-xl px-4 py-5 sm:px-6">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Ticket Timeline</h2>
              <p class="mt-1 text-sm text-slate-600">A plain-language record of status changes, updates, and evidence visible to you.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $timelineEntries->count() }} events</span>
          </div>
          <ol class="mt-5 space-y-4">
            @forelse ($timelineEntries as $entry)
              <li class="relative border-l-2 border-slate-200 pl-4 sm:pl-5">
                <div @class([
                  'absolute -left-1.5 top-1.5 h-3 w-3 rounded-full ring-4 ring-white',
                  'bg-blue-500' => $entry['tone'] === 'blue',
                  'bg-amber-500' => $entry['tone'] === 'amber',
                  'bg-emerald-500' => $entry['tone'] === 'green',
                  'bg-slate-400' => $entry['tone'] === 'slate',
                ])></div>
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-3 sm:px-4">
                  <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="text-sm font-semibold text-slate-900">{{ $entry['headline'] }}</p>
                      <p class="mt-1 text-xs text-slate-500">
                        {{ $entry['created_at']->format('M j, Y H:i') }}
                        @if ($entry['actor'])
                          <span>by {{ $entry['actor'] }}</span>
                        @endif
                      </p>
                    </div>
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase text-slate-600">{{ $entry['type'] }}</span>
                  </div>
                  <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $entry['detail'] }}</p>
                  @if ($entry['note'] ?? null)
                    <p class="mt-2 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $entry['note'] }}</p>
                  @endif
                </div>
              </li>
            @empty
              <li class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                No ticket activity yet.
              </li>
            @endforelse
          </ol>
        </section>

        <section class="bg-white shadow-sm rounded-xl px-4 py-5 sm:px-6 space-y-4">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">Activity &amp; Updates</h2>
            <span class="text-xs text-slate-500">{{ $comments->count() }} notes</span>
          </div>

          <ul class="space-y-4">
            @forelse ($comments as $comment)
              <li class="rounded-lg border border-slate-200 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
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
                <textarea name="body" rows="4" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm" required>{{ old('body') }}</textarea>
              </label>
              @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                  <input type="checkbox" name="is_private" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                  Private note (requester cannot see)
                </label>
              @endif
              <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
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
          @if ($templateMeta && ! empty($templateMeta['evidence_hint']))
            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
              Evidence guidance: {{ $templateMeta['evidence_hint'] }}
            </p>
          @endif
          <ul class="space-y-2 text-sm text-slate-700">
            @forelse (($visibleAttachments ?? collect()) as $attachment)
              <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="font-medium text-slate-800">{{ $attachment->label ?: $attachment->original_name }}</p>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-700">
                      {{ $attachment->kindLabel() }}
                    </span>
                    @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                      <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">
                        {{ $attachment->visibilityLabel() }}
                      </span>
                    @endif
                  </div>
                  <p class="text-xs text-slate-500">
                    {{ $attachment->original_name }} · Uploaded by {{ $attachment->uploader->name }} · {{ number_format(($attachment->size ?? 0) / 1024, 1) }} KB
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
              <div class="grid gap-3 md:grid-cols-2">
                <label class="block text-sm font-medium text-slate-700">
                  Evidence type
                  <select name="kind" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="photo">Photo</option>
                    <option value="screenshot">Screenshot</option>
                    <option value="document">Document</option>
                    <option value="timesheet">Timesheet evidence</option>
                    <option value="other">Other evidence</option>
                  </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                  Evidence label
                  <input type="text" name="label" maxlength="120" placeholder="Optional short description" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </label>
              </div>
              <input type="file" name="attachment" required class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200" accept="image/jpeg,image/png,application/pdf" />
              @if (auth()->user()->hasRole('manager', 'ops_manager', 'hr', 'admin'))
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                  <input type="checkbox" name="visibility" value="internal" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                  Internal only attachment
                </label>
              @endif
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
