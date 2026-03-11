<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApprovalQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_open_approval_workbench_and_filter_pending_items(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'template_key' => 'missed_punch',
            'status' => TicketStatus::New,
            'title' => 'Missed punch correction request',
        ]);

        TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting HR review',
        ]);

        $completedTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'template_key' => 'missed_punch',
            'status' => TicketStatus::Resolved,
            'title' => 'Completed punch correction request',
        ]);

        TicketApproval::create([
            'ticket_id' => $completedTicket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_APPROVED,
            'public_note' => 'Completed by HR.',
            'decided_at' => now()->subMinutes(15),
        ]);

        $this->actingAs($hr)
            ->get(route('tickets.approvals.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSeeText('Approval Workbench')
            ->assertSeeText('Missed punch correction request')
            ->assertSeeText('Awaiting HR review')
            ->assertSeeText('Waiting now')
            ->assertSeeText('Completed Recently');
    }

    public function test_manager_only_sees_manager_approvals_for_managed_departments(): void
    {
        $managedDepartment = Department::factory()->create(['name' => 'Inbound']);
        $otherDepartment = Department::factory()->create(['name' => 'Outbound']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $managedDepartment->id,
        ]);
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $otherDepartment->id,
        ]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);

        $visibleTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $managedDepartment->id,
            'template_key' => 'shift_swap_request',
            'status' => TicketStatus::New,
            'title' => 'Inbound shift swap request',
        ]);

        $hiddenDepartmentTicket = Ticket::factory()->create([
            'requester_id' => $otherEmployee->id,
            'created_for_id' => $otherEmployee->id,
            'department_id' => $otherDepartment->id,
            'template_key' => 'shift_swap_request',
            'status' => TicketStatus::New,
            'title' => 'Outbound shift swap request',
        ]);

        $hiddenHrTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $managedDepartment->id,
            'template_key' => 'missed_punch',
            'status' => TicketStatus::New,
            'title' => 'Inbound missed punch request',
        ]);

        TicketApproval::create([
            'ticket_id' => $visibleTicket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Manager review needed for Inbound.',
        ]);
        TicketApproval::create([
            'ticket_id' => $hiddenDepartmentTicket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Manager review needed for Outbound.',
        ]);
        TicketApproval::create([
            'ticket_id' => $hiddenHrTicket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'HR review needed for attendance fix.',
        ]);

        $this->actingAs($manager)
            ->get(route('tickets.approvals.index'))
            ->assertOk()
            ->assertSeeText('Inbound shift swap request')
            ->assertSeeText('Manager review needed for Inbound.')
            ->assertDontSeeText('Outbound shift swap request')
            ->assertDontSeeText('Inbound missed punch request');
    }

    public function test_employee_cannot_open_approval_workbench(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('tickets.approvals.index'))
            ->assertForbidden();
    }

    public function test_hr_can_approve_from_workbench_and_return_to_queue(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'template_key' => 'missed_punch',
            'status' => TicketStatus::New,
            'title' => 'Missed punch correction request',
        ]);

        $approval = TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting HR review',
        ]);

        $this->actingAs($hr)
            ->from(route('tickets.approvals.index'))
            ->patch(route('tickets.approvals.update', [$ticket, $approval]), [
                'decision' => 'approved',
                'public_note' => 'Clock correction approved from the workbench.',
                'internal_note' => 'Evidence reviewed and accepted.',
            ])
            ->assertRedirect(route('tickets.approvals.index'));

        $this->assertDatabaseHas('ticket_approvals', [
            'id' => $approval->id,
            'status' => TicketApproval::STATUS_APPROVED,
            'approver_id' => $hr->id,
            'public_note' => 'Clock correction approved from the workbench.',
        ]);

        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
    }

    public function test_hr_workbench_groups_paused_and_completed_items(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $pausedTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::WaitingEmployee,
            'title' => 'Missing clock-out details',
        ]);

        $completedTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::Resolved,
            'title' => 'Approved punch correction',
        ]);

        TicketApproval::create([
            'ticket_id' => $pausedTicket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_NEEDS_INFO,
            'public_note' => 'Please confirm the exact clock-out time.',
        ]);

        TicketApproval::create([
            'ticket_id' => $completedTicket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_APPROVED,
            'public_note' => 'Approved and closed by HR.',
            'decided_at' => now()->subHour(),
        ]);

        $this->actingAs($hr)
            ->get(route('tickets.approvals.index'))
            ->assertOk()
            ->assertSeeText('Paused Waiting On Requester')
            ->assertSeeText('Please confirm the exact clock-out time.')
            ->assertSeeText('Completed Recently')
            ->assertSeeText('Approved and closed by HR.');
    }
}
