<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\TicketApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardHrWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_dashboard_shows_people_operations_workspace(): void
    {
        $department = Department::factory()->create(['name' => 'People Support']);
        $hrCategory = Category::factory()->create([
            'name' => 'HR',
            'audience' => 'all',
            'is_sensitive' => true,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'name' => 'Emma Employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $approvalTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $hrCategory->id,
            'template_key' => 'missed_punch',
            'status' => TicketStatus::New,
            'title' => 'Missed punch review for Emma',
        ]);

        TicketApproval::create([
            'ticket_id' => $approvalTicket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
            'public_note' => 'Awaiting HR review',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $hrCategory->id,
            'template_key' => 'shift_swap_request',
            'status' => TicketStatus::InProgress,
            'title' => 'Sensitive schedule adjustment',
        ]);

        RoleChangeRequest::factory()->create([
            'requester_id' => $hr->id,
            'target_user_id' => $employee->id,
            'department_id' => $department->id,
            'requested_role' => 'manager',
            'status' => RoleChangeRequest::STATUS_PENDING,
        ]);

        $announcement = Announcement::factory()->create([
            'title' => 'Policy clarification needed',
            'priority' => 'urgent',
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => null,
            'author_id' => $hr->id,
        ]);
        $announcement->readers()->attach($employee->id, [
            'read_at' => now(),
            'acknowledgement' => Announcement::ACKNOWLEDGEMENT_NEEDS_CLARIFICATION,
            'acknowledged_at' => now(),
        ]);

        $this->actingAs($hr)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('People Operations Queue')
            ->assertSeeText('Approvals Waiting Now')
            ->assertSeeText('Missed punch review for Emma')
            ->assertSeeText('Sensitive People Cases')
            ->assertSeeText('Sensitive schedule adjustment')
            ->assertSeeText('People Tickets')
            ->assertSeeText('Policy Follow-up')
            ->assertSeeText('Policy clarification needed')
            ->assertSeeText('Role Requests')
            ->assertSeeText('Emma Employee');
    }

    public function test_manager_dashboard_does_not_show_hr_workspace(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSeeText('People Operations Queue');
    }

    public function test_sensitive_ticket_detail_explains_public_and_private_hr_updates(): void
    {
        $department = Department::factory()->create(['name' => 'People Support']);
        $hrCategory = Category::factory()->create([
            'name' => 'HR',
            'audience' => 'all',
            'is_sensitive' => true,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $hr = User::factory()->create(['role' => 'hr']);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $hrCategory->id,
            'status' => TicketStatus::InProgress,
            'title' => 'Sensitive HR case',
        ]);

        $this->actingAs($hr)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Sensitive case handling')
            ->assertSeeText('Use public updates for requester-facing decisions and private notes for HR-only evidence');
    }
}
