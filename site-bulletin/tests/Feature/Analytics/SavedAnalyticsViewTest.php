<?php

namespace Tests\Feature\Analytics;

use App\Models\Department;
use App\Models\SavedAnalyticsView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedAnalyticsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_save_and_apply_view(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create();

        $manager->departments()->attach($department->id, ['role' => 'manager']);

        $this->actingAs($manager)
            ->post(route('analytics.views.store'), [
                'name' => 'Inbound weekly',
                'department_id' => $department->id,
                'days' => 14,
            ])
            ->assertRedirect(route('analytics.index', ['saved_view_id' => SavedAnalyticsView::first()->id]));

        $this->assertDatabaseHas('saved_analytics_views', [
            'user_id' => $manager->id,
            'name' => 'Inbound weekly',
            'department_id' => $department->id,
            'days' => 14,
        ]);

        $view = SavedAnalyticsView::first();

        $this->actingAs($manager)
            ->get(route('analytics.index', ['saved_view_id' => $view->id]))
            ->assertOk()
            ->assertSee('Inbound weekly')
            ->assertSee((string) $department->name);
    }
}
