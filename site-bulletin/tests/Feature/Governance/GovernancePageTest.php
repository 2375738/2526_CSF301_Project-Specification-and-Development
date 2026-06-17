<?php

namespace Tests\Feature\Governance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GovernancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_governance_pages(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('governance.index'))
            ->assertOk()
            ->assertSee('Operations Governance Hub');

        $this->actingAs($manager)
            ->get(route('governance.policies'))
            ->assertOk()
            ->assertSee('Policies & Procedures', false);

        $this->actingAs($manager)
            ->get(route('governance.escalation'))
            ->assertOk()
            ->assertSee('Escalation Playbook', false);
    }

    public function test_guest_redirected_from_governance_pages(): void
    {
        $this->get(route('governance.index'))->assertRedirect(route('login'));
    }

    public function test_admin_sees_system_health_checklist_on_governance_hub(): void
    {
        Config::set('app.debug', false);
        Config::set('site_bulletin.demo_login_enabled', false);
        Config::set('site_bulletin.demo_simulation_enabled', false);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('governance.index'))
            ->assertOk()
            ->assertSeeText('System Health Checklist')
            ->assertSeeText('Application debug mode')
            ->assertSeeText('Demo mode boundary')
            ->assertSeeText('Queue driver');
    }

    public function test_manager_does_not_see_admin_system_health_checklist(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('governance.index'))
            ->assertOk()
            ->assertDontSeeText('System Health Checklist');
    }

    public function test_readiness_command_reports_checks(): void
    {
        Config::set('app.debug', false);
        Config::set('site_bulletin.demo_login_enabled', false);
        Config::set('site_bulletin.demo_simulation_enabled', false);

        $this->artisan('site:readiness-check')
            ->expectsOutputToContain('Overall:')
            ->assertExitCode(0);
    }
}
