<?php

namespace App\Http\Controllers\Public;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Services\AuditLogger;
use App\Services\RoleScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketApprovalController extends Controller
{
    public function __construct(protected RoleScopeService $roleScope)
    {
    }

    public function update(Request $request, Ticket $ticket, TicketApproval $approval, AuditLogger $auditLogger): RedirectResponse
    {
        abort_unless($approval->ticket_id === $ticket->id, 404);

        $user = $request->user();
        abort_unless($this->canDecideApproval($user, $ticket, $approval), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected,needs_info'],
            'public_note' => ['nullable', 'string', 'max:1000'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval->markDecision(
            $data['decision'],
            $user,
            $data['public_note'] ?? null,
            $data['internal_note'] ?? null
        );

        $nextQueuedApproval = null;

        if ($data['decision'] === TicketApproval::STATUS_APPROVED) {
            $nextQueuedApproval = $ticket->approvals()
                ->where('status', TicketApproval::STATUS_QUEUED)
                ->orderBy('step_order')
                ->first();
        }

        $ticketComment = match ($data['decision']) {
            TicketApproval::STATUS_APPROVED => $nextQueuedApproval
                ? 'Approval completed for this step. The next review is now waiting.'
                : 'Approval completed: approved.',
            TicketApproval::STATUS_REJECTED => 'Approval completed: rejected.',
            TicketApproval::STATUS_NEEDS_INFO => 'Approval paused: more information requested.',
            default => 'Approval updated.',
        };

        $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $data['public_note'] ?: $ticketComment,
            'is_private' => false,
        ]);

        if (! empty($data['internal_note'])) {
            $ticket->comments()->create([
                'user_id' => $user->id,
                'body' => $data['internal_note'],
                'is_private' => true,
            ]);
        }

        if ($nextQueuedApproval) {
            $nextQueuedApproval->forceFill([
                'status' => TicketApproval::STATUS_PENDING,
            ])->save();
        }

        $nextStatus = match ($data['decision']) {
            TicketApproval::STATUS_APPROVED => $nextQueuedApproval ? TicketStatus::Triaged : TicketStatus::Resolved,
            TicketApproval::STATUS_REJECTED => TicketStatus::Cancelled,
            TicketApproval::STATUS_NEEDS_INFO => TicketStatus::WaitingEmployee,
            default => $ticket->status,
        };

        if ($ticket->status !== $nextStatus) {
            $ticket->markStatus($nextStatus, $user, 'Approval decision recorded.');
        }

        $auditLogger->log('ticket.approval.updated', $approval, [
            'ticket_id' => $ticket->id,
            'approval_id' => $approval->id,
            'decision' => $approval->status,
            'approver_role' => $approval->approver_role,
            'next_approval_id' => $nextQueuedApproval?->id,
        ], $user);

        return back()->with('status', 'Approval decision saved.');
    }

    protected function canDecideApproval($user, Ticket $ticket, TicketApproval $approval): bool
    {
        if ($approval->status !== TicketApproval::STATUS_PENDING) {
            return false;
        }

        if ($approval->approver_role === 'hr') {
            return $user->hasRole('hr', 'admin');
        }

        if ($approval->approver_role === 'manager') {
            if ($this->roleScope->canManageAllDepartments($user)) {
                return true;
            }

            return $this->roleScope->canManageDepartment($user, $ticket->department_id);
        }

        return false;
    }
}
