<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleChangeRequest>
 */
class RoleChangeRequestFactory extends Factory
{
    protected $model = RoleChangeRequest::class;

    public function definition(): array
    {
        $requester = User::factory();
        $target = User::factory();

        return [
            'requester_id' => $requester,
            'target_user_id' => $target,
            'requested_role' => UserRole::Manager->value,
            'department_id' => Department::factory(),
            'justification' => $this->faker->sentence(8),
            'status' => RoleChangeRequest::STATUS_PENDING,
            'approver_id' => null,
            'decision_notes' => null,
            'decided_at' => null,
        ];
    }
}
