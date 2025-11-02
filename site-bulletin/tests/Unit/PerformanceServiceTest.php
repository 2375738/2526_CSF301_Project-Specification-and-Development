<?php

namespace Tests\Unit;

use App\Services\PerformanceService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class PerformanceServiceTest extends TestCase
{
    public function test_risk_flag_triggers_on_latest_snapshot()
    {
        $service = new PerformanceService();

        $data = collect([
            (object) ['week_start' => Carbon::now()->subWeeks(2), 'rank_percentile' => 40],
            (object) ['week_start' => Carbon::now()->subWeeks(1), 'rank_percentile' => 60],
            (object) ['week_start' => Carbon::now(), 'rank_percentile' => 96],
        ]);

        $this->assertTrue($service->riskFlag($data));
    }

    public function test_risk_flag_triggers_on_three_weeks_over_threshold()
    {
        $service = new PerformanceService();

        $data = collect([
            (object) ['week_start' => Carbon::now()->subWeeks(5), 'rank_percentile' => 97],
            (object) ['week_start' => Carbon::now()->subWeeks(4), 'rank_percentile' => 94],
            (object) ['week_start' => Carbon::now()->subWeeks(3), 'rank_percentile' => 99],
            (object) ['week_start' => Carbon::now()->subWeeks(2), 'rank_percentile' => 50],
            (object) ['week_start' => Carbon::now()->subWeeks(1), 'rank_percentile' => 95],
            (object) ['week_start' => Carbon::now(), 'rank_percentile' => 70],
        ]);

        $this->assertTrue($service->riskFlag($data));
    }

    public function test_risk_flag_not_triggered_when_threshold_not_met()
    {
        $service = new PerformanceService();

        $data = collect([
            (object) ['week_start' => Carbon::now()->subWeeks(5), 'rank_percentile' => 80],
            (object) ['week_start' => Carbon::now()->subWeeks(4), 'rank_percentile' => 88],
            (object) ['week_start' => Carbon::now()->subWeeks(3), 'rank_percentile' => 92],
            (object) ['week_start' => Carbon::now()->subWeeks(2), 'rank_percentile' => 91],
            (object) ['week_start' => Carbon::now()->subWeeks(1), 'rank_percentile' => 70],
            (object) ['week_start' => Carbon::now(), 'rank_percentile' => 85],
        ]);

        $this->assertFalse($service->riskFlag($data));
    }
}
