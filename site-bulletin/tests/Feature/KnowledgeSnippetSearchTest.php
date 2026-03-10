<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KnowledgeSnippet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeSnippetSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_search_visible_knowledge_snippets(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        KnowledgeSnippet::factory()->create([
            'title' => 'Scanner reset steps',
            'summary' => 'Quick device recovery.',
            'body' => 'Reset the scanner and reconnect to Wi-Fi.',
            'audience' => 'all',
        ]);

        KnowledgeSnippet::factory()->create([
            'title' => 'Manager escalation guide',
            'body' => 'For leadership use only.',
            'audience' => 'managers',
        ]);

        $this->actingAs($employee)
            ->get(route('knowledge.index', ['q' => 'scanner']))
            ->assertOk()
            ->assertSeeText('Knowledge Snippets')
            ->assertSeeText('Scanner reset steps')
            ->assertDontSeeText('Manager escalation guide');
    }

    public function test_manager_can_see_manager_only_knowledge_snippet(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);

        KnowledgeSnippet::factory()->create([
            'title' => 'Manager escalation guide',
            'body' => 'Collect ticket IDs before escalation.',
            'audience' => 'managers',
            'department_id' => $department->id,
        ]);

        $this->actingAs($manager)
            ->get(route('knowledge.index'))
            ->assertOk()
            ->assertSeeText('Manager escalation guide');
    }
}
