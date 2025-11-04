<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentMetric>
 */
class DepartmentMetricFactory extends Factory
{
    protected $model = DepartmentMetric::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'metric_date' => now()->subDays(rand(0, 30))->toDateString(),
            'open_tickets' => rand(0, 50),
            'sla_breaches' => rand(0, 10),
            'messages_sent' => rand(0, 100),
            'avg_first_response_minutes' => rand(30, 600),
            'avg_resolution_minutes' => rand(120, 2880),
        ];
    }
}
