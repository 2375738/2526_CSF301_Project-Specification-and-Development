<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priority = fake()->randomElement(['low', 'medium', 'high', 'critical']);
        $status = fake()->randomElement(['new', 'triaged', 'in_progress', 'waiting_employee', 'resolved', 'closed']);

        return [
            'requester_id' => \App\Models\User::factory(),
            'assignee_id' => null,
            'category_id' => \App\Models\Category::factory(),
            'priority' => $priority,
            'status' => $status,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'location' => fake()->optional()->words(3, true),
            'closed_at' => $status === 'closed' ? now() : null,
        ];
    }
}
