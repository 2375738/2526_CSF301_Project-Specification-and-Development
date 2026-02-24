<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\PerformanceSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTrendPanelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_employee_trend_panel(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        PerformanceSnapshot::factory()->count(3)->create([
            'user_id' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Employee Performance Trend')
            ->assertSeeText('Quality vs Productivity Trend');
    }

    public function test_manager_sees_department_trend_panel(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);

        foreach (range(0, 3) as $offset) {
            DepartmentMetric::factory()->create([
                'department_id' => $department->id,
                'metric_date' => now()->subDays($offset)->toDateString(),
            ]);
        }

        $this->actingAs($manager)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Department Trend Overview')
            ->assertSeeText('Benchmark Comparison');
    }
}
