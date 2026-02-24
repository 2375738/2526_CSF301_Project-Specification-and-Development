<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementReadFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_announcement_marks_it_as_read(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create([
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertOk();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_open_department_announcement_for_other_department(): void
    {
        $userDepartment = Department::factory()->create();
        $otherDepartment = Department::factory()->create();

        $user = User::factory()->create([
            'primary_department_id' => $userDepartment->id,
        ]);

        $announcement = Announcement::factory()->create([
            'audience' => 'department',
            'department_id' => $otherDepartment->id,
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('announcements.show', $announcement))
            ->assertNotFound();
    }
}

