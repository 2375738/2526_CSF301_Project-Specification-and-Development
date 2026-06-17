<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardNewsWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_news_widget_with_unread_signal_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $unread = Announcement::factory()->create([
            'title' => 'Urgent Safety Update',
            'priority' => 'urgent',
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $read = Announcement::factory()->create([
            'title' => 'General Ops Memo',
            'priority' => 'low',
            'audience' => 'all',
            'is_active' => true,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $read->markReadFor($user);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Latest News & Updates')
            ->assertSeeText('Urgent Safety Update')
            ->assertSeeText('Unread')
            ->assertSee(route('announcements.show', $unread), false);
    }
}
