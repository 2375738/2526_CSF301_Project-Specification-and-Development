<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\KnowledgeSnippet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeSnippet>
 */
class KnowledgeSnippetFactory extends Factory
{
    protected $model = KnowledgeSnippet::class;

    public function definition(): array
    {
        $audience = fake()->boolean(25) ? 'department' : 'all';

        return [
            'title' => fake()->sentence(4),
            'summary' => fake()->sentence(10),
            'body' => fake()->paragraphs(2, true),
            'department_id' => $audience === 'department'
                ? Department::query()->inRandomOrder()->value('id')
                : null,
            'audience' => $audience,
            'is_active' => true,
            'order' => fake()->numberBetween(0, 20),
        ];
    }
}
