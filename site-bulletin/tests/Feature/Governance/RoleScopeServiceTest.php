<?php

namespace Tests\Feature\Governance;

use App\Models\Department;
use App\Models\User;
use App\Services\RoleScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_scope_is_limited_to_their_departments(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $employee = User::factory()->create(['primary_department_id' => $department->id]);

        $service = app(RoleScopeService::class);

        $this->assertFalse($service->canViewAllDepartments($employee));
        $this->assertTrue($service->canViewDepartment($employee, $department->id));
        $this->assertFalse($service->canViewDepartment($employee, $otherDepartment->id));
        $this->assertFalse($service->canManageDepartment($employee, $department->id));
    }

    public function test_manager_scope_is_limited_to_managed_departments(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->manager()->create();
        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager']);

        $service = app(RoleScopeService::class);

        $this->assertFalse($service->canManageAllDepartments($manager));
        $this->assertTrue($service->canManageDepartment($manager, $managedDepartment->id));
        $this->assertFalse($service->canManageDepartment($manager, $otherDepartment->id));
    }

    public function test_ops_hr_and_admin_have_site_wide_department_scope(): void
    {
        $department = Department::factory()->create();
        $service = app(RoleScopeService::class);

        foreach (['ops_manager', 'hr', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue($service->canViewAllDepartments($user));
            $this->assertTrue($service->canManageAllDepartments($user));
            $this->assertTrue($service->canManageDepartment($user, $department->id));
            $this->assertNull($service->manageableDepartmentIds($user));
        }
    }
}
