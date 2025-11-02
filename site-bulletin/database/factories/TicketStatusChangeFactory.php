<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketStatusChange>
 */
class TicketStatusChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->randomElement(['new', 'triaged', 'in_progress', 'waiting_employee']);
        $to = fake()->randomElement(['triaged', 'in_progress', 'waiting_employee', 'resolved', 'closed']);

        return [
            'ticket_id' => \App\Models\Ticket::factory(),
            'user_id' => \App\Models\User::factory(),
            'from_status' => $from,
            'to_status' => $to,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
