<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'event_type' => $this->faker->randomElement([
                'ticket.status.updated',
                'user.role.changed',
                'sla.threshold.adjusted',
            ]),
            'auditable_type' => null,
            'auditable_id' => null,
            'payload' => [
                'before' => ['status' => 'new'],
                'after' => ['status' => 'in_progress'],
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'occurred_at' => now(),
        ];
    }
}
