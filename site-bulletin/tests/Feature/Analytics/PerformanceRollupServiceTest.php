<?php

namespace Tests\Feature\Analytics;

use App\Models\Department;
use App\Models\PerformanceRollup;
use App\Models\PerformanceSample;
use App\Models\User;
use App\Services\PerformanceRollupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PerformanceRollupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollup_service_builds_weighted_hourly_and_daily_buckets(): void
    {
        $department = Department::factory()->create();
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        foreach ([
            ['2026-03-16 10:00:00', 10, 90],
            ['2026-03-16 10:15:00', 20, 100],
            ['2026-03-16 11:00:00', 30, 70],
        ] as [$recordedAt, $units, $quality]) {
            PerformanceSample::create([
                'user_id' => $employee->id,
                'recorded_at' => $recordedAt,
                'units_per_hour' => $units,
                'quality_score' => $quality,
            ]);
        }

        $result = app(PerformanceRollupService::class)
            ->refreshRange(Carbon::parse('2026-03-16'), Carbon::parse('2026-03-16 23:59:59'));

        $this->assertSame(3, $result['samples']);
        $this->assertSame(2, $result['hourly']);
        $this->assertSame(1, $result['daily']);

        $this->assertDatabaseHas('performance_rollups', [
            'user_id' => $employee->id,
            'bucket_type' => 'hour',
            'bucket_start' => '2026-03-16 10:00:00',
            'sample_count' => 2,
            'avg_units_per_hour' => 15,
            'avg_quality_score' => 95,
        ]);

        $daily = PerformanceRollup::query()
            ->where('user_id', $employee->id)
            ->where('bucket_type', 'day')
            ->firstOrFail();

        $this->assertSame(3, $daily->sample_count);
        $this->assertSame(20.0, $daily->avg_units_per_hour);
        $this->assertSame(86.7, $daily->avg_quality_score);
    }

    public function test_rollup_command_can_prune_raw_samples_after_aggregation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-17 12:00:00'));

        try {
            $employee = User::factory()->create(['role' => 'employee']);

            PerformanceSample::create([
                'user_id' => $employee->id,
                'recorded_at' => '2026-03-01 10:00:00',
                'units_per_hour' => 35,
                'quality_score' => 94,
            ]);

            PerformanceSample::create([
                'user_id' => $employee->id,
                'recorded_at' => '2026-06-16 10:00:00',
                'units_per_hour' => 40,
                'quality_score' => 96,
            ]);

            $this->artisan('performance:rollup-samples', [
                '--start' => '2026-03-01 00:00:00',
                '--end' => '2026-06-17 12:00:00',
                '--prune-raw-after-days' => 30,
            ])->assertExitCode(0);

            $this->assertDatabaseHas('performance_rollups', [
                'user_id' => $employee->id,
                'bucket_type' => 'day',
                'bucket_start' => '2026-03-01 00:00:00',
            ]);

            $this->assertDatabaseMissing('performance_samples', [
                'recorded_at' => '2026-03-01 10:00:00',
            ]);

            $this->assertDatabaseHas('performance_samples', [
                'recorded_at' => '2026-06-16 10:00:00',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }
}
