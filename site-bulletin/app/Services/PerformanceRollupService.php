<?php

namespace App\Services;

use App\Models\PerformanceRollup;
use App\Models\PerformanceSample;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PerformanceRollupService
{
    public function refreshRange(Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();
        $result = ['hourly' => 0, 'daily' => 0, 'samples' => 0];

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();

            $samples = PerformanceSample::query()
                ->whereBetween('recorded_at', [$dayStart, $dayEnd])
                ->orderBy('recorded_at')
                ->get(['user_id', 'recorded_at', 'units_per_hour', 'quality_score']);

            if ($samples->isEmpty()) {
                continue;
            }

            $hourly = $this->buildRollups($samples, 'hour', fn (PerformanceSample $sample) => $sample->recorded_at->copy()->startOfHour());
            $daily = $this->buildRollups($samples, 'day', fn (PerformanceSample $sample) => $sample->recorded_at->copy()->startOfDay());

            $this->upsertRollups($hourly);
            $this->upsertRollups($daily);

            $result['hourly'] += $hourly->count();
            $result['daily'] += $daily->count();
            $result['samples'] += $samples->count();
        }

        return $result;
    }

    public function pruneRawSamplesOlderThan(int $days): int
    {
        $cutoff = now()->subDays($days)->startOfDay();

        return PerformanceSample::query()
            ->where('recorded_at', '<', $cutoff)
            ->delete();
    }

    public function seriesForUsers(array $userIds, ?Carbon $reference = null): array
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        if (empty($userIds)) {
            return $this->emptySeries();
        }

        $end = $this->latestRollupEnd($userIds) ?? ($reference ?? now());
        $daily = $this->dailySeries($userIds, $end);
        $last24 = $this->hourlySeries($userIds, $end, 24, 3);

        return [
            '7d' => $daily,
            '24h' => $last24,
            '3h' => collect(),
        ];
    }

    public function hasRollupsForUsers(array $userIds): bool
    {
        $userIds = collect($userIds)->filter()->unique()->values()->all();

        return ! empty($userIds)
            && PerformanceRollup::query()->whereIn('user_id', $userIds)->exists();
    }

    protected function latestRollupEnd(array $userIds): ?Carbon
    {
        $latest = PerformanceRollup::query()
            ->whereIn('user_id', $userIds)
            ->max('bucket_start');

        return $latest ? Carbon::parse($latest) : null;
    }

    protected function dailySeries(array $userIds, Carbon $end): Collection
    {
        return $this->weightedRows($userIds, 'day', $end->copy()->subDays(6)->startOfDay(), $end->copy()->endOfDay())
            ->map(fn (array $row) => [
                'label' => $row['bucket']->format('D'),
                'productivity' => $row['productivity'],
                'quality' => $row['quality'],
                'from_date' => $row['bucket']->toDateString(),
                'to_date' => $row['bucket']->toDateString(),
            ])
            ->values();
    }

    protected function hourlySeries(array $userIds, Carbon $end, int $hours, int $bucketHours): Collection
    {
        return $this->weightedRows($userIds, 'hour', $end->copy()->subHours($hours), $end)
            ->groupBy(fn (array $row) => $row['bucket']->copy()->setTime((int) floor($row['bucket']->hour / $bucketHours) * $bucketHours, 0)->format('Y-m-d H:i:s'))
            ->map(function (Collection $rows, string $bucket) {
                $sampleCount = max(1, (int) $rows->sum('sample_count'));
                $productivity = $rows->sum(fn (array $row) => $row['productivity'] * $row['sample_count']) / $sampleCount;
                $quality = $rows->sum(fn (array $row) => $row['quality'] * $row['sample_count']) / $sampleCount;
                $time = Carbon::parse($bucket);

                return [
                    'label' => $time->format('H:i'),
                    'productivity' => round($productivity, 1),
                    'quality' => round($quality, 1),
                    'from_date' => $time->toDateString(),
                    'to_date' => $time->toDateString(),
                ];
            })
            ->values();
    }

    protected function weightedRows(array $userIds, string $bucketType, Carbon $start, Carbon $end): Collection
    {
        return PerformanceRollup::query()
            ->whereIn('user_id', $userIds)
            ->where('bucket_type', $bucketType)
            ->whereBetween('bucket_start', [$start, $end])
            ->orderBy('bucket_start')
            ->get(['bucket_start', 'sample_count', 'avg_units_per_hour', 'avg_quality_score'])
            ->groupBy(fn (PerformanceRollup $rollup) => $rollup->bucket_start->format('Y-m-d H:i:s'))
            ->map(function (Collection $rollups, string $bucket) {
                $sampleCount = max(1, (int) $rollups->sum('sample_count'));
                $productivity = $rollups->sum(fn (PerformanceRollup $rollup) => $rollup->avg_units_per_hour * $rollup->sample_count) / $sampleCount;
                $quality = $rollups->sum(fn (PerformanceRollup $rollup) => $rollup->avg_quality_score * $rollup->sample_count) / $sampleCount;

                return [
                    'bucket' => Carbon::parse($bucket),
                    'sample_count' => $sampleCount,
                    'productivity' => round($productivity, 1),
                    'quality' => round($quality, 1),
                ];
            })
            ->values();
    }

    protected function buildRollups(Collection $samples, string $bucketType, callable $bucketResolver): Collection
    {
        return $samples
            ->groupBy(fn (PerformanceSample $sample) => $sample->user_id . '|' . $bucketResolver($sample)->format('Y-m-d H:i:s'))
            ->map(function (Collection $bucketSamples) use ($bucketType, $bucketResolver) {
                $first = $bucketSamples->first();

                return [
                    'user_id' => $first->user_id,
                    'bucket_type' => $bucketType,
                    'bucket_start' => $bucketResolver($first)->toDateTimeString(),
                    'sample_count' => $bucketSamples->count(),
                    'avg_units_per_hour' => round((float) $bucketSamples->avg('units_per_hour'), 1),
                    'avg_quality_score' => round((float) $bucketSamples->avg('quality_score'), 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values();
    }

    protected function upsertRollups(Collection $rollups): void
    {
        $rollups->chunk(1000)->each(function (Collection $chunk) {
            PerformanceRollup::query()->upsert(
                $chunk->all(),
                ['user_id', 'bucket_type', 'bucket_start'],
                ['sample_count', 'avg_units_per_hour', 'avg_quality_score', 'updated_at']
            );
        });
    }

    protected function emptySeries(): array
    {
        return [
            '7d' => collect(),
            '24h' => collect(),
            '3h' => collect(),
        ];
    }
}
