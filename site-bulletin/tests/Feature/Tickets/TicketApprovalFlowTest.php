<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_swap_template_creates_pending_manager_approval_and_shows_requester_status(): void
    {
        $department = Department::factory()->create(['name' => 'Inbound']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);
        $hrCategory = Category::factory()->create(['name' => 'HR', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);

        $this->actingAs($employee)
            ->post(route('tickets.store'), [
                'template' => 'shift_swap_request',
                'category_id' => $hrCategory->id,
                'title' => 'Shift swap approval request',
                'description' => 'Shift swap request needs review.',
                'detail_answers' => ['2026-03-14 10:00', '2026-03-16 10:00', 'Jane Doe'],
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();

        $this->assertNotNull($ticket);
        $this->assertDatabaseHas('ticket_approvals', [
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('ticket_approvals', [
            'ticket_id' => $ticket->id,
            'step_order' => 2,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_QUEUED,
        ]);

        $this->actingAs($employee)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Approval Status')
            ->assertSeeText('Approval progress')
            ->assertSeeText('Pending approval')
            ->assertSeeText('Awaiting manager approval')
            ->assertSeeText('Queued for later review')
            ->assertSeeText('Awaiting weekday HR review')
            ->assertSeeText('Manager approval is the current step. HR finalization will only start after the manager signs off.');
    }

    public function test_manager_can_approve_shift_swap_for_managed_department(): void
    {
        $department = Department::factory()->create(['name' => 'Inbound']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'template_key' => 'shift_swap_request',
            'status' => TicketStatus::New,
            'title' => 'Shift swap approval request',
        ]);

        $managerApproval = TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting manager approval',
        ]);
        $hrApproval = TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 2,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_QUEUED,
            'public_note' => 'Awaiting weekday HR review',
        ]);

        $this->actingAs($manager)
            ->patch(route('tickets.approvals.update', [$ticket, $managerApproval]), [
                'decision' => 'approved',
                'public_note' => 'Manager review approved. HR review is now pending.',
                'internal_note' => 'Capacity impact checked and accepted.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_approvals', [
            'id' => $managerApproval->id,
            'status' => TicketApproval::STATUS_APPROVED,
            'approver_id' => $manager->id,
            'public_note' => 'Manager review approved. HR review is now pending.',
        ]);
        $this->assertDatabaseHas('ticket_approvals', [
            'id' => $hrApproval->id,
            'status' => TicketApproval::STATUS_PENDING,
        ]);

        $this->assertSame(TicketStatus::Triaged, $ticket->fresh()->status);

        $this->actingAs($employee)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Approved')
            ->assertSeeText('Manager review approved. HR review is now pending.')
            ->assertSeeText('Awaiting weekday HR review')
            ->assertSeeText('HR review is pending. Night HR coverage can usually request more information or handle basic attendance fixes, but final approval may wait for the weekday HR team.')
            ->assertSeeText('Approval History')
            ->assertSeeText('Step 1')
            ->assertDontSeeText('Capacity impact checked and accepted.');
    }

    public function test_hr_can_request_more_info_for_missed_punch_ticket(): void
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
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting HR review',
        ]);

        $this->actingAs($hr)
            ->patch(route('tickets.approvals.update', [$ticket, $approval]), [
                'decision' => 'needs_info',
                'public_note' => 'Please confirm the exact clock-out time.',
                'internal_note' => 'Night cover can only handle basic punch corrections.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_approvals', [
            'id' => $approval->id,
            'status' => TicketApproval::STATUS_NEEDS_INFO,
            'approver_id' => $hr->id,
        ]);

        $this->assertSame(TicketStatus::WaitingEmployee, $ticket->fresh()->status);
    }

    public function test_hr_final_approval_resolves_shift_swap_and_completes_approval_chain(): void
    {
        $department = Department::factory()->create(['name' => 'Inbound']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'template_key' => 'shift_swap_request',
            'status' => TicketStatus::Triaged,
            'title' => 'Shift swap approval request',
        ]);

        TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_APPROVED,
            'public_note' => 'Manager review approved. HR review is now pending.',
            'approver_id' => $hr->id,
            'decided_at' => now()->subHour(),
        ]);

        $hrApproval = TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 2,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting weekday HR review',
        ]);

        $this->actingAs($hr)
            ->patch(route('tickets.approvals.update', [$ticket, $hrApproval]), [
                'decision' => 'approved',
                'public_note' => 'HR final approval recorded. Shift swap is confirmed.',
                'internal_note' => 'Weekday HR finalized the schedule change.',
            ])
            ->assertRedirect();

        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);

        $this->actingAs($employee)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('All approval steps complete')
            ->assertSeeText('HR final approval recorded. Shift swap is confirmed.')
            ->assertSeeText('Approval History')
            ->assertDontSeeText('Weekday HR finalized the schedule change.');
    }

    public function test_hr_sees_pending_approval_queue_on_ticket_index(): void
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

        $this->actingAs($hr)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('Approvals Waiting For You')
            ->assertSeeText('Missed punch correction request')
            ->assertSeeText('Pending approval');
    }
}
