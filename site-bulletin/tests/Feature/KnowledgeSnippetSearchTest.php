<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\KnowledgeSnippet;
use App\Models\Link;
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

    public function test_help_search_returns_grouped_visible_results(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $category = Category::factory()->create([
            'name' => 'Scanner Resources',
            'audience' => 'all',
        ]);

        Link::factory()->create([
            'category_id' => $category->id,
            'label' => 'Scanner battery guide',
            'url' => 'https://example.test/scanner-battery',
        ]);

        KnowledgeSnippet::factory()->create([
            'title' => 'Scanner reset steps',
            'summary' => 'Quick device recovery.',
            'body' => 'Reset the scanner and reconnect to Wi-Fi.',
            'audience' => 'all',
        ]);

        Announcement::factory()->create([
            'title' => 'Scanner dock maintenance',
            'body' => 'Scanner docks will be serviced tonight.',
            'priority' => 'medium',
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);

        $this->actingAs($employee)
            ->get(route('knowledge.index', ['q' => 'scanner']))
            ->assertOk()
            ->assertSeeText('Quick links')
            ->assertSeeText('Scanner battery guide')
            ->assertSeeText('Announcements')
            ->assertSeeText('Scanner dock maintenance')
            ->assertSeeText('Scanner reset steps')
            ->assertSeeText('Start guided report');
    }

    public function test_help_search_hides_manager_only_quick_links_from_employee(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $managerCategory = Category::factory()->create([
            'name' => 'Manager Scanner Resources',
            'audience' => 'managers',
        ]);

        Link::factory()->create([
            'category_id' => $managerCategory->id,
            'label' => 'Scanner escalation rota',
        ]);

        $this->actingAs($employee)
            ->get(route('knowledge.index', ['q' => 'scanner']))
            ->assertOk()
            ->assertDontSeeText('Scanner escalation rota');
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

    public function test_admin_can_see_manager_only_knowledge_snippet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        KnowledgeSnippet::factory()->create([
            'title' => 'Leadership escalation guide',
            'body' => 'Use the senior incident path for site-wide blockers.',
            'audience' => 'managers',
        ]);

        $this->actingAs($admin)
            ->get(route('knowledge.index'))
            ->assertOk()
            ->assertSeeText('Leadership escalation guide');
    }
}
