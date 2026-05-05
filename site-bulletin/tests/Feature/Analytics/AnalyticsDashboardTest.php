<?php

namespace Tests\Feature\Analytics;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_dashboard_handles_enum_cast_ticket_priorities(): void
    {
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create([
            'priority' => 'critical',
            'status' => 'in_progress',
        ]);

        Ticket::factory()->create([
            'priority' => 'medium',
            'status' => 'waiting_employee',
        ]);

        $this->actingAs($admin)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSeeText('Analytics');
    }
}
