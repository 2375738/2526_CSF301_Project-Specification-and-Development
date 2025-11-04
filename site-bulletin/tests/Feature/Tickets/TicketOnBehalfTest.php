<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketOnBehalfTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_ticket_for_employee_in_managed_department(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create(['audience' => 'all', 'department_id' => null]);

        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'primary_department_id' => $department->id,
        ]);

        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => false]);
        $employee->departments()->attach($department->id, ['role' => 'member', 'is_primary' => true]);

        $response = $this->actingAs($manager)->post(route('tickets.store'), [
            'category_id' => $category->id,
            'title' => 'Safety hazard in inbound area',
            'description' => 'Forklift spill needs cleanup.',
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
        ]);

        $response->assertRedirect();

        $ticket = Ticket::where('title', 'Safety hazard in inbound area')->first();

        $this->assertNotNull($ticket, 'Ticket was not created');
        $this->assertSame($manager->id, $ticket->requester_id);
        $this->assertSame($employee->id, $ticket->created_for_id);
        $this->assertSame($department->id, $ticket->department_id);
        $this->assertDatabaseHas('ticket_status_changes', [
            'ticket_id' => $ticket->id,
            'to_status' => 'new',
        ]);
    }

    public function test_manager_cannot_create_ticket_for_employee_outside_managed_departments(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $category = Category::factory()->create(['audience' => 'all', 'department_id' => null]);

        $manager = User::factory()->manager()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $managedEmployee = User::factory()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $externalEmployee = User::factory()->create([
            'primary_department_id' => $otherDepartment->id,
        ]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);
        $managedEmployee->departments()->attach($managedDepartment->id, ['role' => 'member', 'is_primary' => true]);
        $externalEmployee->departments()->attach($otherDepartment->id, ['role' => 'member', 'is_primary' => true]);

        $this->actingAs($manager)
            ->post(route('tickets.store'), [
                'category_id' => $category->id,
                'title' => 'Unmanaged employee issue',
                'description' => 'Attempting to escalate.',
                'created_for_id' => $externalEmployee->id,
                'department_id' => $otherDepartment->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tickets', [
            'title' => 'Unmanaged employee issue',
        ]);
    }

    public function test_department_defaults_to_employees_primary_when_not_provided(): void
    {
        $department = Department::factory()->create();
        $category = Category::factory()->create(['audience' => 'all', 'department_id' => null]);

        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'primary_department_id' => $department->id,
        ]);

        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);
        $employee->departments()->attach($department->id, ['role' => 'member', 'is_primary' => true]);

        $this->actingAs($manager)
            ->post(route('tickets.store'), [
                'category_id' => $category->id,
                'title' => 'Auto department selection',
                'description' => 'Should pick the employee department.',
                'created_for_id' => $employee->id,
            ])
            ->assertRedirect();

        $ticket = Ticket::where('title', 'Auto department selection')->first();

        $this->assertNotNull($ticket);
        $this->assertSame($employee->primary_department_id, $ticket->department_id);
    }
}
