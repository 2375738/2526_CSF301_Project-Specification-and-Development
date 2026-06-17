<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_login_can_authenticate_user_by_role_and_department(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $response = $this->post(route('demo.login'), [
            'role' => 'employee',
            'department_id' => $department->id,
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_demo_login_can_authenticate_all_demo_role_types(): void
    {
        foreach ([
            UserRole::Employee,
            UserRole::Manager,
            UserRole::OpsManager,
            UserRole::Hr,
            UserRole::Admin,
        ] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'email' => $role->value.'@example.com',
            ]);

            $response = $this->post(route('demo.login'), [
                'role' => $role->value,
            ]);

            $this->assertAuthenticatedAs($user);
            $response->assertRedirect(route('dashboard', absolute: false));

            auth()->logout();
            $this->flushSession();
        }
    }

    public function test_demo_login_ignores_department_filter_for_site_wide_roles(): void
    {
        $department = Department::factory()->create();
        $admin = User::factory()->admin()->create([
            'primary_department_id' => null,
        ]);

        $response = $this->post(route('demo.login'), [
            'role' => UserRole::Admin->value,
            'department_id' => $department->id,
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_demo_login_get_redirects_to_login_page(): void
    {
        $this->get(route('demo.login.show'))
            ->assertRedirect(route('login'));
    }

    public function test_demo_login_rejects_invalid_role_value(): void
    {
        Department::factory()->create();

        $response = $this->from(route('login'))->post(route('demo.login'), [
            'role' => 'contractor',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }
}
