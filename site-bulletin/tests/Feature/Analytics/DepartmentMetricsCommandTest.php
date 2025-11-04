<?php

namespace Tests\Feature\Analytics;

use App\Console\Commands\RecalculateDepartmentMetrics;
use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DepartmentMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_metrics_for_departments(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create();
        $manager->departments()->attach($department->id, ['role' => 'manager']);

        Ticket::factory()->create([
            'department_id' => $department->id,
            'priority' => 'high',
            'status' => 'in_progress',
            'sla_first_response_breached' => true,
            'notified_first_response_breach' => false,
        ]);

        $date = Carbon::now()->toDateString();

        $this->artisan('analytics:recalculate-departments', ['--date' => $date])
            ->assertExitCode(RecalculateDepartmentMetrics::SUCCESS);

        $this->assertDatabaseHas('department_metrics', [
            'department_id' => $department->id,
            'metric_date' => $date . ' 00:00:00',
        ]);

        $this->assertDatabaseHas('department_metrics', [
            'department_id' => null,
            'metric_date' => $date . ' 00:00:00',
        ]);
    }
}
