<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_open_triage_board_and_see_grouped_queues(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $category = Category::factory()->create([
            'name' => 'Facilities',
            'audience' => 'all',
        ]);
        $hr = User::factory()->create(['role' => 'hr']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'new',
            'assignee_id' => null,
            'title' => 'Unassigned network printer issue',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
            'sla_resolution_breached' => true,
            'title' => 'Breached dock door investigation',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'waiting_employee',
            'title' => 'Waiting on employee badge photo',
        ]);

        $this->actingAs($hr)
            ->get(route('tickets.triage'))
            ->assertOk()
            ->assertSeeText('Support Triage Board')
            ->assertSeeText('Unassigned New')
            ->assertSeeText('Breached Queue')
            ->assertSeeText('Waiting On Employee')
            ->assertSeeText('Ownership Load')
            ->assertSeeText('By Category')
            ->assertSeeText('By Department')
            ->assertSeeText('Unassigned network printer issue')
            ->assertSeeText('Breached dock door investigation')
            ->assertSeeText('Waiting on employee badge photo');
    }

    public function test_employee_cannot_open_triage_board(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('tickets.triage'))
            ->assertForbidden();
    }
}
