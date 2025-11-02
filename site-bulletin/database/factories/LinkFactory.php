<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->sentence(3),
            'url' => $this->faker->url(),
            'is_hot' => $this->faker->boolean(20),
            'is_active' => true,
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
