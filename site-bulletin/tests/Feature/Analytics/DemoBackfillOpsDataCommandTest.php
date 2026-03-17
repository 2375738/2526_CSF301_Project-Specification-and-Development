<?php

namespace Tests\Feature\Analytics;

use App\Console\Commands\BackfillDemoOperationsData;
use App\Models\Category;
use App\Models\Department;
use App\Models\PerformanceSample;
use App\Models\PerformanceSnapshot;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoBackfillOpsDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_backfills_deterministic_fifteen_minute_samples_and_snapshots(): void
    {
        $department = Department::factory()->create([
            'target_units_per_hour' => 44,
            'target_quality_pct' => 96,
        ]);
        Category::factory()->create([
            'name' => 'Operations',
            'audience' => 'all',
        ]);

        User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $this->artisan('demo:backfill-ops-data', [
            '--refresh' => true,
            '--weeks' => 1,
            '--end' => '2026-03-16 12:10:00',
        ])->assertExitCode(BackfillDemoOperationsData::SUCCESS);

        $this->assertDatabaseHas('performance_samples', [
            'recorded_at' => '2026-03-16 12:00:00',
        ]);

        $this->assertDatabaseMissing('performance_samples', [
            'recorded_at' => '2026-03-16 12:10:00',
        ]);

        $this->assertGreaterThan(0, PerformanceSample::query()->count());
        $this->assertGreaterThan(0, PerformanceSnapshot::query()->count());
        $this->assertGreaterThan(0, Ticket::query()->whereNotNull('simulation_key')->count());
        $this->assertGreaterThan(0, TicketStatusChange::query()->count());
    }

    public function test_command_can_incrementally_fill_a_gap_without_refreshing_history(): void
    {
        $department = Department::factory()->create();
        Category::factory()->create([
            'name' => 'Operations',
            'audience' => 'all',
        ]);
        User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $this->artisan('demo:backfill-ops-data', [
            '--refresh' => true,
            '--weeks' => 1,
            '--end' => '2026-03-16 09:00:00',
        ])->assertExitCode(BackfillDemoOperationsData::SUCCESS);

        $baselineCount = PerformanceSample::query()->count();

        $this->artisan('demo:backfill-ops-data', [
            '--end' => '2026-03-16 09:30:00',
        ])->assertExitCode(BackfillDemoOperationsData::SUCCESS);

        $this->assertSame($baselineCount + 2, PerformanceSample::query()->count());
        $this->assertDatabaseHas('performance_samples', [
            'recorded_at' => '2026-03-16 09:30:00',
        ]);
        $this->assertGreaterThan(0, Ticket::query()->whereNotNull('simulation_key')->count());
    }
}
