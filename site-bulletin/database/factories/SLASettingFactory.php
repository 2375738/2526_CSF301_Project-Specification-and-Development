<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SLASetting>
 */
class SLASettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priority = fake()->randomElement(['low', 'medium', 'high', 'critical']);

        return [
            'priority' => $priority,
            'first_response_minutes' => match ($priority) {
                'critical' => 60,
                'high' => 120,
                'medium' => 240,
                default => 480,
            },
            'resolution_minutes' => match ($priority) {
                'critical' => 240,
                'high' => 720,
                'medium' => 1440,
                default => 2880,
            },
            'pause_statuses' => ['waiting_employee'],
        ];
    }
}
