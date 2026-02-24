<?php

namespace Tests\Feature\Auth;

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

    public function test_demo_login_rejects_invalid_role_value(): void
    {
        Department::factory()->create();

        $response = $this->from(route('login'))->post(route('demo.login'), [
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }
}

