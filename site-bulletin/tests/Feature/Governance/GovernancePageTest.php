<?php

namespace Tests\Feature\Governance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
