<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
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

        $audience = $this->faker->randomElement(['all', 'department', 'managers']);
        $departmentId = $audience === 'department'
            ? Department::query()->inRandomOrder()->value('id')
            : null;

        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(2, true),
            'starts_at' => $start,
            'ends_at' => $end,
            'is_pinned' => $this->faker->boolean(20),
            'is_active' => $this->faker->boolean(90),
            'audience' => $audience,
            'department_id' => $departmentId,
            'author_id' => User::factory(),
        ];
    }
}
