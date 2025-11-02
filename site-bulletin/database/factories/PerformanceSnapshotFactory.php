<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerformanceSnapshot>
 */
class PerformanceSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'week_start' => \Illuminate\Support\Carbon::now()->startOfWeek()->subWeeks(fake()->numberBetween(0, 10)),
            'units_per_hour' => fake()->numberBetween(60, 170),
            'rank_percentile' => fake()->numberBetween(1, 100),
        ];
    }
}
