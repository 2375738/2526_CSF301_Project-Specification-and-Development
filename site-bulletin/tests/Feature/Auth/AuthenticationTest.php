<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_shows_demo_preset_buttons_when_demo_users_exist(): void
    {
        $department = Department::factory()->create(['name' => 'Inbound']);

        User::factory()->create([
            'name' => 'John Doe',
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        User::factory()->manager()->create([
            'name' => 'Operations Manager',
            'primary_department_id' => $department->id,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Quick Demo Access')
            ->assertSee('Enter as Employee')
            ->assertSee('Enter as Manager');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
