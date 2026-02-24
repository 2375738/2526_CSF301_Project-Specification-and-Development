<?php

namespace Tests\Feature\Notifications;

use App\Models\Announcement;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewAnnouncement;
use App\Notifications\NewMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationPreferencesDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_notifications_respect_user_preference(): void
    {
        Notification::fake();

        $sender = User::factory()->create();
        $receiverOptIn = User::factory()->create(['email_notifications_enabled' => true]);
        $receiverOptOut = User::factory()->create(['email_notifications_enabled' => false]);

        $conversation = Conversation::factory()->for($sender, 'creator')->create();
        $conversation->participants()->sync([
            $sender->id => ['role' => 'owner', 'last_read_at' => now()],
            $receiverOptIn->id => ['role' => 'member', 'last_read_at' => null],
            $receiverOptOut->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Message preference check.',
            'is_system' => false,
        ]);

        Notification::assertSentTo($receiverOptIn, NewMessage::class);
        Notification::assertNotSentTo($receiverOptOut, NewMessage::class);
        Notification::assertNotSentTo($sender, NewMessage::class);
    }

    public function test_announcement_notifications_respect_user_preference(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $receiverOptIn = User::factory()->create(['email_notifications_enabled' => true]);
        $receiverOptOut = User::factory()->create(['email_notifications_enabled' => false]);

        Announcement::factory()->create([
            'author_id' => $author->id,
            'audience' => 'all',
            'is_active' => true,
        ]);

        Notification::assertSentTo($receiverOptIn, NewAnnouncement::class);
        Notification::assertNotSentTo($receiverOptOut, NewAnnouncement::class);
        Notification::assertNotSentTo($author, NewAnnouncement::class);
    }
}

