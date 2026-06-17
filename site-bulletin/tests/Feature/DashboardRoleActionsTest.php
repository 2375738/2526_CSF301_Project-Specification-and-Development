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

class DashboardRoleActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_prioritized_next_actions(): void
    {
        $department = Department::factory()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::WaitingEmployee,
            'title' => 'Confirm scanner replacement',
        ]);

        Announcement::factory()->create([
            'title' => 'Urgent dock safety update',
            'priority' => 'urgent',
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);

        $this->actingAs($employee)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Next Actions')
            ->assertSeeText('Reply to a waiting ticket')
            ->assertSeeText('Confirm scanner replacement')
            ->assertSeeText('Read urgent update')
            ->assertSeeText('Urgent dock safety update');
    }

    public function test_manager_next_actions_are_limited_to_managed_department_work(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $managedDepartment->id,
        ]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);

        $managedTicket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $managedDepartment->id,
            'status' => TicketStatus::InProgress,
            'sla_resolution_breached' => true,
            'title' => 'Managed breached queue item',
        ]);

        Ticket::factory()->create([
            'department_id' => $otherDepartment->id,
            'status' => TicketStatus::InProgress,
            'sla_resolution_breached' => true,
            'title' => 'Unmanaged breached queue item',
        ]);

        TicketApproval::create([
            'ticket_id' => $managedTicket->id,
            'step_order' => 1,
            'step_key' => 'manager_review',
            'approver_role' => 'manager',
            'status' => TicketApproval::STATUS_PENDING,
        ]);

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Next Actions')
            ->assertSeeText('Clear breached tickets')
            ->assertSeeText('1 open ticket outside SLA.')
            ->assertSeeText('Review pending approvals')
            ->assertDontSeeText('2 open tickets outside SLA.');
    }

    public function test_ops_manager_next_actions_use_site_wide_pressure(): void
    {
        $firstDepartment = Department::factory()->create();
        $secondDepartment = Department::factory()->create();
        $opsManager = User::factory()->create(['role' => 'ops_manager']);

        Ticket::factory()->create([
            'department_id' => $firstDepartment->id,
            'status' => TicketStatus::New,
            'assignee_id' => null,
            'title' => 'Inbound unassigned issue',
        ]);

        Ticket::factory()->create([
            'department_id' => $secondDepartment->id,
            'status' => TicketStatus::InProgress,
            'sla_first_response_breached' => true,
            'title' => 'Outbound breached issue',
        ]);

        $this->actingAs($opsManager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Balance unassigned work')
            ->assertSeeText('2 open tickets without an owner.')
            ->assertSeeText('Reduce site SLA pressure')
            ->assertSeeText('1 open ticket outside SLA across the site.');
    }

    public function test_hr_next_actions_show_approvals_sensitive_cases_and_role_requests(): void
    {
        $department = Department::factory()->create();
        $hr = User::factory()->create(['role' => 'hr']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $sensitiveCategory = Category::factory()->create([
            'name' => 'HR',
            'audience' => 'all',
            'is_sensitive' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $sensitiveCategory->id,
            'status' => TicketStatus::New,
        ]);

        TicketApproval::create([
            'ticket_id' => $ticket->id,
            'step_order' => 1,
            'step_key' => 'hr_review',
            'approver_role' => 'hr',
            'status' => TicketApproval::STATUS_PENDING,
        ]);

        RoleChangeRequest::factory()->create([
            'requester_id' => $employee->id,
            'target_user_id' => $employee->id,
            'department_id' => $department->id,
            'status' => RoleChangeRequest::STATUS_PENDING,
        ]);

        $this->actingAs($hr)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Complete HR approvals')
            ->assertSeeText('Review sensitive people cases')
            ->assertSeeText('Decide role requests');
    }

    public function test_admin_next_actions_show_configuration_health(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Assign missing departments')
            ->assertSeeText('1 user need a primary department.');
    }
}
