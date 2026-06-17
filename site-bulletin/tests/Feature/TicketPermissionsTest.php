<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_ticket_in_managed_department(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $ticket = Ticket::factory()->create([
            'department_id' => $department->id,
        ]);

        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);

        $this->assertTrue($manager->can('view', $ticket));
        $this->assertTrue($manager->can('update', $ticket));
    }

    public function test_manager_cannot_view_ticket_in_unmanaged_department(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $ticket = Ticket::factory()->create([
            'department_id' => $otherDepartment->id,
        ]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);

        $this->assertFalse($manager->can('view', $ticket));
        $this->assertFalse($manager->can('update', $ticket));
    }

    public function test_ops_manager_can_view_ticket_in_any_department(): void
    {
        $department = Department::factory()->create();
        $opsManager = User::factory()->create(['role' => 'ops_manager']);
        $ticket = Ticket::factory()->create([
            'department_id' => $department->id,
        ]);

        $this->assertTrue($opsManager->can('view', $ticket));
        $this->assertTrue($opsManager->can('update', $ticket));
    }
}
