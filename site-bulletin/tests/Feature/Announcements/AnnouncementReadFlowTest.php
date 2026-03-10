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

    public function test_user_can_acknowledge_announcement_as_understood(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create([
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->patch(route('announcements.acknowledge', $announcement), [
                'acknowledgement' => 'understood',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'acknowledgement' => 'understood',
        ]);
    }

    public function test_user_can_update_acknowledgement_without_creating_duplicate_receipts(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create([
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->patch(route('announcements.acknowledge', $announcement), [
                'acknowledgement' => 'needs_clarification',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->patch(route('announcements.acknowledge', $announcement), [
                'acknowledgement' => 'understood',
            ])
            ->assertRedirect();

        $this->assertSame(1, $announcement->readers()->where('users.id', $user->id)->count());
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'acknowledgement' => 'understood',
        ]);
    }

    public function test_manager_sees_acknowledgement_summary_on_announcement_detail(): void
    {
        $manager = User::factory()->manager()->create();
        $readerA = User::factory()->create();
        $readerB = User::factory()->create();
        $readerC = User::factory()->create();

        $announcement = Announcement::factory()->create([
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'author_id' => $manager->id,
        ]);

        $announcement->readers()->syncWithoutDetaching([
            $readerA->id => ['read_at' => now(), 'acknowledgement' => 'understood', 'acknowledged_at' => now()],
            $readerB->id => ['read_at' => now(), 'acknowledgement' => 'needs_clarification', 'acknowledged_at' => now()],
            $readerC->id => ['read_at' => now()],
        ]);

        $this->actingAs($manager)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSeeText('Acknowledgement Summary')
            ->assertSeeText('Understood')
            ->assertSeeText('Need Clarification')
            ->assertSeeText('Read Only')
            ->assertSeeText('4');
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

    public function test_admin_can_open_manager_only_announcement(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'audience' => 'managers',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSeeText($announcement->title);
    }
}
