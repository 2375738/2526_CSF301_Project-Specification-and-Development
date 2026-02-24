<?php

namespace Tests\Feature\Announcements;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementCreationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_create_announcement(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $department = Department::factory()->create();

        $this->actingAs($employee)
            ->post(route('announcements.store'), [
                'title' => 'Employee Attempt',
                'body' => 'Should not create',
                'priority' => 'medium',
                'audience' => 'department',
                'department_id' => $department->id,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_create_department_announcement_for_managed_department(): void
    {
        $manager = User::factory()->manager()->create();
        $department = Department::factory()->create();
        $department->members()->attach($manager->id, ['role' => 'manager', 'is_primary' => true]);

        $this->actingAs($manager)
            ->post(route('announcements.store'), [
                'title' => 'Inbound Safety Brief',
                'body' => 'PPE reminder for shift start.',
                'priority' => 'high',
                'audience' => 'department',
                'department_id' => $department->id,
            ])
            ->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Inbound Safety Brief',
            'audience' => 'department',
            'department_id' => $department->id,
            'priority' => 'high',
            'author_id' => $manager->id,
        ]);
    }

    public function test_manager_cannot_create_all_audience_announcement(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->from(route('announcements.index'))
            ->post(route('announcements.store'), [
                'title' => 'Site-wide Attempt',
                'body' => 'Manager should not post site-wide.',
                'priority' => 'medium',
                'audience' => 'all',
            ])
            ->assertSessionHasErrors('audience')
            ->assertRedirect(route('announcements.index'));
    }

    public function test_hr_can_create_all_audience_announcement(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);

        $this->actingAs($hr)
            ->post(route('announcements.store'), [
                'title' => 'HR Site-wide Update',
                'body' => 'Benefits enrollment timeline.',
                'priority' => 'urgent',
                'audience' => 'all',
            ])
            ->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'HR Site-wide Update',
            'audience' => 'all',
            'priority' => 'urgent',
            'author_id' => $hr->id,
        ]);
    }
}

