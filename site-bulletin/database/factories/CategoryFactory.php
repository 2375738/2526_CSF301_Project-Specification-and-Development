<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $audience = $this->faker->boolean(30) ? 'department' : 'all';

        return [
            'name' => $this->faker->unique()->words(2, true),
            'order' => $this->faker->numberBetween(0, 10),
            'is_sensitive' => false,
            'audience' => $audience,
            'department_id' => $audience === 'department'
                ? Department::query()->inRandomOrder()->value('id')
                : null,
        ];
    }
}
