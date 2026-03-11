<article class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="space-y-2">
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ticket #{{ $approval->ticket->id }}</span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-700">
          {{ ucfirst(str_replace('_', ' ', $approval->step_key)) }}
        </span>
        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-blue-700">
          {{ $approval->publicStatusLabel() }}
        </span>
        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-700">
          {{ strtoupper($approval->approver_role) }}
        </span>
      </div>
      <h2 class="text-base font-semibold text-slate-900">
        <a href="{{ route('tickets.show', $approval->ticket) }}" class="hover:underline">{{ $approval->ticket->title }}</a>
      </h2>
      <p class="text-sm text-slate-600">
        {{ $approval->ticket->category?->name ?? 'Uncategorised' }}
        @if ($approval->ticket->department)
          · {{ $approval->ticket->department->name }}
        @endif
        @if ($approval->ticket->requester)
          · Requester: {{ $approval->ticket->requester->name }}
        @endif
        @if ($approval->ticket->createdFor && $approval->ticket->created_for_id !== $approval->ticket->requester_id)
          · For: {{ $approval->ticket->createdFor->name }}
        @endif
      </p>
      @if ($approval->public_note)
        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ $approval->public_note }}</p>
      @endif
    </div>
    <div class="min-w-[240px] space-y-2 text-sm text-slate-600">
      <p>Step {{ $approval->step_order }}</p>
      <p>Updated {{ $approval->updated_at->diffForHumans() }}</p>
      @if ($approval->approver)
        <p>Last decided by {{ $approval->approver->name }}</p>
      @endif
      <a href="{{ route('tickets.show', $approval->ticket) }}" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-sm font-medium text-slate-700 hover:bg-slate-300">
        Open ticket
      </a>
    </div>
  </div>

  @if ($approval->status === \App\Models\TicketApproval::STATUS_PENDING)
    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Decide this step</h3>
          <p class="mt-1 text-sm text-slate-600">
            Add a requester-visible update and, if needed, keep internal rationale separate for manager or HR context.
          </p>
        </div>
        <p class="text-xs text-slate-500">
          Approval actions update the ticket status and may activate the next queued step automatically.
        </p>
      </div>

      <form action="{{ route('tickets.approvals.update', [$approval->ticket, $approval]) }}" method="POST" class="mt-4 space-y-3">
        @csrf
        @method('PATCH')
        <div class="grid gap-3 lg:grid-cols-2">
          <label class="block text-sm font-medium text-slate-700">
            Public note
            <textarea name="public_note" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Visible to the requester"></textarea>
          </label>
          <label class="block text-sm font-medium text-slate-700">
            Internal note
            <textarea name="internal_note" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Visible only to manager, HR, or admin"></textarea>
          </label>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="submit" name="decision" value="approved" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
            Approve
          </button>
          <button type="submit" name="decision" value="needs_info" class="inline-flex items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600">
            Need Info
          </button>
          <button type="submit" name="decision" value="rejected" class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
            Reject
          </button>
        </div>
      </form>
    </div>
  @elseif ($approval->status === \App\Models\TicketApproval::STATUS_QUEUED)
    <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
      This step is queued behind an earlier approval and cannot be decided yet.
    </div>
  @elseif ($approval->status === \App\Models\TicketApproval::STATUS_NEEDS_INFO)
    <div class="mt-4 rounded-xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
      This approval is paused until the requester adds more information.
    </div>
  @else
    <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
      This approval step is complete. Open the ticket for the full decision history.
    </div>
  @endif
</article>
