<?php

namespace App\Services;

use App\Models\PerformanceSample;
use App\Models\PerformanceSnapshot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DemoOperationsSimulationService
{
    public function ensureFreshSamples(?Carbon $reference = null, int $historyWeeks = 6): array
    {
        $end = $this->alignToQuarterHour($reference ?? now());
        $latestRecordedAt = PerformanceSample::query()->max('recorded_at');

        if (! $latestRecordedAt) {
            return $this->seedHistoricalWindow($historyWeeks, $end);
        }

        $latest = $this->alignToQuarterHour(Carbon::parse($latestRecordedAt));

        if ($latest->greaterThanOrEqualTo($end)) {
            return [
                'mode' => 'noop',
                'start' => $latest->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'generated_intervals' => 0,
                'users' => 0,
            ];
        }

        return $this->generateRange($latest->copy()->addMinutes(15), $end);
    }

    public function seedHistoricalWindow(int $weeks = 6, ?Carbon $reference = null): array
    {
        $end = $this->alignToQuarterHour($reference ?? now());
        $start = $this->alignToQuarterHour($end->copy()->subWeeks($weeks));

        PerformanceSnapshot::query()->delete();
        PerformanceSample::query()->delete();

        return $this->generateRange($start, $end);
    }

    public function backfillRange(Carbon $start, Carbon $end): array
    {
        return $this->generateRange(
            $this->alignToQuarterHour($start),
            $this->alignToQuarterHour($end)
        );
    }

    protected function generateRange(Carbon $start, Carbon $end): array
    {
        if ($start->greaterThan($end)) {
            return [
                'mode' => 'noop',
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'generated_intervals' => 0,
                'users' => 0,
            ];
        }

        $users = User::query()
            ->with(['primaryDepartment', 'departments'])
            ->get();

        if ($users->isEmpty()) {
            return [
                'mode' => 'noop',
                'start' => $start->toDateTimeString(),
                'end' => $end->toDateTimeString(),
                'generated_intervals' => 0,
                'users' => 0,
            ];
        }

        $timestamps = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMinutes(15)) {
            $timestamps[] = $cursor->copy();
        }

        $now = now();
        $upsertRows = [];

        foreach ($users as $user) {
            $profileDepartment = $user->primaryDepartment ?: $user->departments->first();
            $targetUnits = (float) ($profileDepartment?->target_units_per_hour ?: 42.0);
            $targetQuality = (float) ($profileDepartment?->target_quality_pct ?: 95.0);

            foreach ($timestamps as $recordedAt) {
                $upsertRows[] = [
                    'user_id' => $user->id,
                    'recorded_at' => $recordedAt->copy(),
                    'units_per_hour' => $this->generateUnitsPerHour($user->id, $recordedAt, $targetUnits),
                    'quality_score' => $this->generateQualityScore($user->id, $recordedAt, $targetQuality),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($upsertRows) >= 2000) {
                    PerformanceSample::query()->upsert(
                        $upsertRows,
                        ['user_id', 'recorded_at'],
                        ['units_per_hour', 'quality_score', 'updated_at']
                    );

                    $upsertRows = [];
                }
            }
        }

        if (! empty($upsertRows)) {
            PerformanceSample::query()->upsert(
                $upsertRows,
                ['user_id', 'recorded_at'],
                ['units_per_hour', 'quality_score', 'updated_at']
            );
        }

        $this->refreshSnapshotsForWeeks($users, $start, $end, $now);
        return [
            'mode' => 'backfill',
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'generated_intervals' => count($timestamps),
            'users' => $users->count(),
        ];
    }

    protected function refreshSnapshotsForWeeks(Collection $users, Carbon $start, Carbon $end, Carbon $now): void
    {
        $weekStarts = collect();

        for (
            $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
            $cursor->lessThanOrEqualTo($end);
            $cursor->addWeek()
        ) {
            $weekStarts->push($cursor->copy());
        }

        $snapshotRows = [];

        foreach ($users as $user) {
            foreach ($weekStarts as $weekStart) {
                $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

                $samples = PerformanceSample::query()
                    ->where('user_id', $user->id)
                    ->whereBetween('recorded_at', [$weekStart, $weekEnd])
                    ->get(['units_per_hour', 'quality_score']);

                if ($samples->isEmpty()) {
                    continue;
                }

                $avgUnits = round((float) $samples->avg('units_per_hour'));
                $avgQuality = round((float) $samples->avg('quality_score'), 1);

                $snapshotRows[] = [
                    'user_id' => $user->id,
                    'week_start' => $weekStart->toDateString(),
                    'units_per_hour' => (int) $avgUnits,
                    'rank_percentile' => (int) round(max(1, min(99, 100 - $avgQuality))),
                    'quality_score' => $avgQuality,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($snapshotRows)) {
            PerformanceSnapshot::query()->upsert(
                $snapshotRows,
                ['user_id', 'week_start'],
                ['units_per_hour', 'rank_percentile', 'quality_score', 'updated_at']
            );
        }
    }

    protected function generateUnitsPerHour(int $userId, Carbon $recordedAt, float $targetUnits): float
    {
        $hourFraction = ($recordedAt->hour + ($recordedAt->minute / 60)) / 24;
        $dailyWave = sin(($hourFraction * M_PI * 2) - 0.8);
        $weeklyWave = sin((($recordedAt->dayOfWeekIso / 7) * M_PI * 2) + ($userId % 5));
        $shiftBias = $this->seededRatio("shift-bias-{$userId}") - 0.5;
        $noise = $this->seededRatio("sample-{$userId}-{$recordedAt->format('YmdHi')}-uph") - 0.5;

        $factor = 0.92
            + ($dailyWave * 0.10)
            + ($weeklyWave * 0.05)
            + ($shiftBias * 0.08)
            + ($noise * 0.12);

        return round(max(8, min(180, $targetUnits * $factor)), 1);
    }

    protected function generateQualityScore(int $userId, Carbon $recordedAt, float $targetQuality): float
    {
        $hourFraction = ($recordedAt->hour + ($recordedAt->minute / 60)) / 24;
        $dailyWave = sin(($hourFraction * M_PI * 2) - 0.8);
        $weeklyWave = sin((($recordedAt->dayOfWeekIso / 7) * M_PI * 2) + ($userId % 5));
        $disciplineBias = $this->seededRatio("quality-bias-{$userId}") - 0.5;
        $noise = $this->seededRatio("sample-{$userId}-{$recordedAt->format('YmdHi')}-quality") - 0.5;

        $factor = 0.985
            + ($dailyWave * 0.015)
            + ($weeklyWave * 0.01)
            + ($disciplineBias * 0.03)
            + ($noise * 0.05);

        return round(max(70, min(100, $targetQuality * $factor)), 1);
    }

    protected function alignToQuarterHour(Carbon $value): Carbon
    {
        $alignedMinute = (int) (floor($value->minute / 15) * 15);

        return $value->copy()->setMinute($alignedMinute)->setSecond(0);
    }

    protected function seededRatio(string $seed): float
    {
        return (float) sprintf('%u', crc32($seed)) / 4294967295;
    }
}
