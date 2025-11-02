<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->optional(0.7)->dateTimeBetween('-1 week', '+1 week');
        $end = $start
            ? $this->faker->optional(0.5)->dateTimeBetween($start, (clone $start)->modify('+7 days'))
            : null;

        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(2, true),
            'starts_at' => $start,
            'ends_at' => $end,
            'is_pinned' => $this->faker->boolean(20),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
