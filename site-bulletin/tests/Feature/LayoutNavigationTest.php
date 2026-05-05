<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_core_shell_links_without_governance(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('My Work')
            ->assertSee('Messages')
            ->assertSee('Tickets')
            ->assertDontSee('Announcements')
            ->assertDontSee('Governance');
    }

    public function test_manager_sees_governance_and_analytics_links(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Analytics')
            ->assertSee('Governance');
    }

    public function test_authenticated_user_sees_sidebar_collapse_control(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Collapse Sidebar')
            ->assertSee('Expand Sidebar');
    }
}
