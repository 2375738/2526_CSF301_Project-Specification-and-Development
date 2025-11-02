<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PerformanceService
{
    /**
     * Determine if the latest performance snapshot should raise a risk flag.
     *
     * @param  \Illuminate\Support\Collection|array  $snapshots
     */
    public function riskFlag(Collection|array $snapshots): bool
    {
        $collection = $snapshots instanceof Collection ? $snapshots : collect($snapshots);

        $ordered = $collection
            ->filter(fn ($row) => $this->parseWeekStart($row) !== null && $this->extractRank($row) !== null)
            ->sortByDesc(fn ($row) => $this->parseWeekStart($row))
            ->values();

        if ($ordered->isEmpty()) {
            return false;
        }

        $latest = $ordered->first();
        if ($this->extractRank($latest) >= 95) {
            return true;
        }

        $recent = $ordered->take(6);

        return $recent->filter(fn ($row) => $this->extractRank($row) >= 95)->count() >= 3;
    }

    private function parseWeekStart(mixed $row): ?Carbon
    {
        $value = data_get($row, 'week_start');

        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    private function extractRank(mixed $row): ?int
    {
        $rank = data_get($row, 'rank_percentile');

        if ($rank === null) {
            return null;
        }

        return (int) $rank;
    }
}
