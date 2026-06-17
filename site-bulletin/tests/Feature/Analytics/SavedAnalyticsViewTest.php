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

    public function test_manager_cannot_save_view_for_unmanaged_department(): void
    {
        $managedDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $manager = User::factory()->manager()->create();

        $manager->departments()->attach($managedDepartment->id, ['role' => 'manager']);

        $this->actingAs($manager)
            ->post(route('analytics.views.store'), [
                'name' => 'Outbound weekly',
                'department_id' => $otherDepartment->id,
                'days' => 14,
            ])
            ->assertForbidden();
    }

    public function test_ops_manager_can_save_site_wide_analytics_view(): void
    {
        $opsManager = User::factory()->create(['role' => 'ops_manager']);

        $this->actingAs($opsManager)
            ->post(route('analytics.views.store'), [
                'name' => 'Site-wide weekly',
                'department_id' => null,
                'days' => 7,
            ])
            ->assertRedirect(route('analytics.index', ['saved_view_id' => SavedAnalyticsView::first()->id]));

        $this->assertDatabaseHas('saved_analytics_views', [
            'user_id' => $opsManager->id,
            'name' => 'Site-wide weekly',
            'department_id' => null,
            'days' => 7,
        ]);
    }
}
