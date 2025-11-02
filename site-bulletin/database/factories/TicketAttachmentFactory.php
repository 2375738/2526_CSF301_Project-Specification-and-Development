<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fakeFile = fake()->filePath();

        return [
            'ticket_id' => \App\Models\Ticket::factory(),
            'user_id' => \App\Models\User::factory(),
            'path' => 'attachments/' . \Illuminate\Support\Str::uuid() . '.dat',
            'original_name' => basename($fakeFile),
            'mime' => fake()->mimeType(),
            'size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }
}
