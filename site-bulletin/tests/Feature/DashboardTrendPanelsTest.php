<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\Conversation;
use App\Models\ManagerRelationship;
use App\Models\Category;
use App\Models\Message;
use App\Models\PerformanceSample;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\PerformanceSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTrendPanelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_employee_trend_panel_on_my_work_page(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        PerformanceSnapshot::factory()
            ->count(3)
            ->sequence(
                ['week_start' => now()->startOfWeek()->subWeeks(2)->toDateString()],
                ['week_start' => now()->startOfWeek()->subWeek()->toDateString()],
                ['week_start' => now()->startOfWeek()->toDateString()],
            )
            ->create([
                'user_id' => $employee->id,
            ]);

        $this->actingAs($employee)
            ->get(route('my-work.index'))
            ->assertOk()
            ->assertSeeText('My Work')
            ->assertSeeText('Employee Performance Trend')
            ->assertSeeText('Quality vs Productivity Trend');
    }

    public function test_employee_sees_my_work_today_summary_with_actionable_ticket_context(): void
    {
        $department = Department::factory()->create([
            'name' => 'Inbound',
            'ops_code' => 'INB',
            'description' => 'Inbound dock operations and intake flow.',
            'target_units_per_hour' => 47,
            'target_quality_pct' => 97,
        ]);
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $category = Category::factory()->create([
            'name' => 'Scanner Support',
            'audience' => 'all',
            'department_id' => null,
        ]);

        ManagerRelationship::create([
            'manager_id' => $employee->id,
            'reports_to_id' => $manager->id,
            'relationship_type' => 'direct',
        ]);

        PerformanceSnapshot::factory()->create([
            'user_id' => $employee->id,
            'week_start' => now()->startOfWeek()->subWeek()->toDateString(),
            'units_per_hour' => 44,
            'rank_percentile' => 48,
        ]);

        PerformanceSnapshot::factory()->create([
            'user_id' => $employee->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'units_per_hour' => 49,
            'rank_percentile' => 41,
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'assignee_id' => $manager->id,
            'status' => 'waiting_employee',
            'title' => 'Scanner battery swap needed',
            'sla_resolution_breached' => true,
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'new',
            'title' => 'Damaged tote label issue',
        ]);

        $this->actingAs($employee)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('My Work Today')
            ->assertSeeText('Inbound')
            ->assertSeeText('Manager:')
            ->assertSee("Today's Focus", false)
            ->assertSeeText('Shift Targets')
            ->assertSeeText('Attention Needed')
            ->assertSeeText('Action needed from you')
            ->assertSeeText('Scanner battery swap needed')
            ->assertSee(route('my-work.index'), false)
            ->assertDontSeeText('Employee Performance Trend');
    }

    public function test_manager_is_redirected_away_from_my_work_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('my-work.index'))
            ->assertRedirect(route('home'));
    }

    public function test_manager_sees_department_trend_panel(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $category = Category::factory()->create([
            'name' => 'Operations',
            'audience' => 'all',
        ]);

        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);
        $employee->departments()->attach($department->id, ['role' => 'member', 'is_primary' => true]);

        foreach (range(0, 3) as $offset) {
            DepartmentMetric::factory()->create([
                'department_id' => $department->id,
                'metric_date' => now()->subDays($offset)->toDateString(),
            ]);
        }

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
            'title' => 'Dock gate sensor outage',
            'sla_resolution_breached' => true,
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $department->id,
            'category_id' => $category->id,
            'status' => 'waiting_employee',
            'title' => 'Need employee confirmation on pallet count',
        ]);

        $conversation = Conversation::factory()->for($manager, 'creator')->create([
            'subject' => 'Inbound escalation',
            'type' => 'direct',
        ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => null],
            $employee->id => ['role' => 'member', 'last_read_at' => now()->subHour()],
        ]);

        Message::factory()->for($conversation)->for($employee, 'sender')->create([
            'body' => 'Need support on dock 4.',
            'created_at' => now(),
        ]);

        RoleChangeRequest::factory()->create([
            'requester_id' => $manager->id,
            'target_user_id' => $employee->id,
            'department_id' => $department->id,
            'status' => RoleChangeRequest::STATUS_PENDING,
        ]);

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Attention Queue')
            ->assertSeeText('Breached Tickets')
            ->assertSeeText('Waiting On Employee')
            ->assertSeeText('Unread Conversations')
            ->assertSeeText('Pending Role Requests')
            ->assertSeeText('Dock gate sensor outage')
            ->assertSeeText('Need employee confirmation on pallet count')
            ->assertSeeText('Inbound escalation')
            ->assertSeeText('Team Size')
            ->assertSeeText('Units Processed')
            ->assertSeeText('Avg Productivity')
            ->assertSeeText('Quality Score')
            ->assertSeeText('Department Trend Overview')
            ->assertSeeText('Department Operational Performance')
            ->assertSeeText('Team Performance:')
            ->assertSeeText('SLA Health')
            ->assertSeeText('Within SLA')
            ->assertSeeText('Daily Breach Drilldown')
            ->assertSeeText('Ticket Type Breakdown')
            ->assertSee('breached=1', false)
            ->assertSeeText('Benchmark Comparison');
    }

    public function test_manager_benchmark_resolution_hours_are_clamped_to_sane_range(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);

        DepartmentMetric::factory()->create([
            'department_id' => $department->id,
            'metric_date' => now()->toDateString(),
            'avg_resolution_minutes' => 54300, // 905 hours raw
            'open_tickets' => 10,
            'sla_breaches' => 1,
        ]);

        DepartmentMetric::factory()->create([
            'department_id' => null,
            'metric_date' => now()->toDateString(),
            'avg_resolution_minutes' => 54300, // 905 hours raw
            'open_tickets' => 25,
            'sla_breaches' => 3,
        ]);

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Dept 72.0')
            ->assertDontSeeText('Dept 905.0');
    }

    public function test_manager_dashboard_falls_back_to_managed_department_when_primary_department_is_not_managed(): void
    {
        $managedDepartment = Department::factory()->create(['name' => 'Support']);
        $otherDepartment = Department::factory()->create(['name' => 'Outbound']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $otherDepartment->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $managedDepartment->id,
        ]);
        $category = Category::factory()->create([
            'name' => 'Operations',
            'audience' => 'all',
        ]);

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager', 'is_primary' => true]);
        $employee->departments()->attach($managedDepartment->id, ['role' => 'member', 'is_primary' => true]);

        DepartmentMetric::factory()->create([
            'department_id' => $managedDepartment->id,
            'metric_date' => now()->toDateString(),
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'department_id' => $managedDepartment->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
            'title' => 'Managed department queue item',
            'sla_resolution_breached' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Attention Queue')
            ->assertSeeText('Managed department queue item')
            ->assertSeeText('Department Trend Overview');
    }

    public function test_manager_performance_summary_uses_employee_samples_as_source_of_truth(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 12:00:00'));

        try {
            $department = Department::factory()->create([
                'name' => 'Inbound',
                'ops_code' => 'INB',
                'target_units_per_hour' => 40,
                'target_quality_pct' => 95,
                'day_shift_start' => '10:00:00',
                'day_shift_end' => '20:00:00',
            ]);
            $manager = User::factory()->manager()->create([
                'primary_department_id' => $department->id,
            ]);
            $employee = User::factory()->create([
                'role' => 'employee',
                'primary_department_id' => $department->id,
            ]);

            $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);
            $employee->departments()->attach($department->id, ['role' => 'member', 'is_primary' => true]);

            DepartmentMetric::factory()->create([
                'department_id' => $department->id,
                'metric_date' => now()->toDateString(),
                'open_tickets' => 0,
                'sla_breaches' => 0,
            ]);

            foreach (['11:00:00', '11:15:00', '11:30:00', '11:45:00', '12:00:00'] as $time) {
                PerformanceSample::create([
                    'user_id' => $employee->id,
                    'recorded_at' => '2026-03-16 ' . $time,
                    'units_per_hour' => 40,
                    'quality_score' => 95,
                ]);

                PerformanceSample::create([
                    'user_id' => $manager->id,
                    'recorded_at' => '2026-03-16 ' . $time,
                    'units_per_hour' => 200,
                    'quality_score' => 70,
                ]);
            }

            $this->actingAs($manager)
                ->get(route('home'))
                ->assertOk()
                ->assertSeeText('Team Size')
                ->assertSeeText('Active now /')
                ->assertSeeText('Avg Productivity')
                ->assertSeeText('40.0 /hr')
                ->assertSeeText('Quality Score')
                ->assertSeeText('95.0%')
                ->assertSeeText('Units Processed')
                ->assertSeeText('50')
                ->assertDontSeeText('200.0 /hr');
        } finally {
            Carbon::setTestNow();
        }
    }
}
