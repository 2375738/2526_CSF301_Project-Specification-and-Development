<?php

namespace Tests\Feature\Governance;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_submit_role_change_for_managed_employee(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create(['primary_department_id' => $department->id]);
        $employee = User::factory()->create(['primary_department_id' => $department->id]);

        $manager->departments()->attach($department->id, ['role' => 'manager']);
        $employee->departments()->attach($department->id, ['role' => 'member']);

        $response = $this->actingAs($manager)->post(route('role-requests.store'), [
            'target_user_id' => $employee->id,
            'requested_role' => UserRole::Manager->value,
            'department_id' => $department->id,
            'justification' => 'Covering team lead shifts.',
        ]);

        $response->assertRedirect(route('role-requests.index'));

        $this->assertDatabaseHas('role_change_requests', [
            'requester_id' => $manager->id,
            'target_user_id' => $employee->id,
            'requested_role' => UserRole::Manager->value,
            'status' => RoleChangeRequest::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'role.request.created',
            'actor_id' => $manager->id,
        ]);
    }

    public function test_manager_cannot_submit_for_unmanaged_employee(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $manager = User::factory()->manager()->create();
        $managedEmployee = User::factory()->create(['primary_department_id' => $managedDepartment->id]);
        $externalEmployee = User::factory()->create(['primary_department_id' => $otherDepartment->id]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager']);
        $managedEmployee->departments()->attach($managedDepartment->id, ['role' => 'member']);
        $externalEmployee->departments()->attach($otherDepartment->id, ['role' => 'member']);

        $this->actingAs($manager)
            ->post(route('role-requests.store'), [
                'target_user_id' => $externalEmployee->id,
                'requested_role' => UserRole::Manager->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('role_change_requests', [
            'target_user_id' => $externalEmployee->id,
        ]);
    }
}
