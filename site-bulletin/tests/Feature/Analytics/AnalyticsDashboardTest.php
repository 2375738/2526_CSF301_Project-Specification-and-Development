<?php

namespace Tests\Feature\Analytics;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AnalyticsExportService;
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

    public function test_manager_analytics_export_is_limited_to_managed_departments(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $managedDepartment->id,
        ]);
        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);

        Ticket::factory()->create([
            'department_id' => $managedDepartment->id,
            'title' => 'Visible managed ticket',
        ]);

        Ticket::factory()->create([
            'department_id' => $otherDepartment->id,
            'title' => 'Hidden unmanaged ticket',
        ]);

        $export = app(AnalyticsExportService::class)->generateTicketExport($manager);

        $titles = $export['rows']->pluck(1);

        $this->assertTrue($titles->contains('Visible managed ticket'));
        $this->assertFalse($titles->contains('Hidden unmanaged ticket'));
    }

    public function test_ops_manager_analytics_export_is_site_wide(): void
    {
        $firstDepartment = Department::factory()->create();
        $secondDepartment = Department::factory()->create();
        $opsManager = User::factory()->create(['role' => 'ops_manager']);

        Ticket::factory()->create([
            'department_id' => $firstDepartment->id,
            'title' => 'First department ticket',
        ]);

        Ticket::factory()->create([
            'department_id' => $secondDepartment->id,
            'title' => 'Second department ticket',
        ]);

        $export = app(AnalyticsExportService::class)->generateTicketExport($opsManager);

        $titles = $export['rows']->pluck(1);

        $this->assertTrue($titles->contains('First department ticket'));
        $this->assertTrue($titles->contains('Second department ticket'));
    }
}
