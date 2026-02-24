<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationSearchPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_index_supports_search_and_shows_recent_preview(): void
    {
        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);

        $matchingConversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Forklift Safety Reminder',
                'type' => 'direct',
            ]);

        $matchingConversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::factory()->for($matchingConversation)->for($manager, 'sender')->create([
            'body' => 'Please review the forklift checklist before shift.',
        ]);

        $otherConversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Canteen Menu',
                'type' => 'direct',
            ]);

        $otherConversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        Message::factory()->for($otherConversation)->for($manager, 'sender')->create([
            'body' => 'New lunch options announced.',
        ]);

        $this->actingAs($employee)
            ->get(route('messages.index', ['q' => 'forklift']))
            ->assertOk()
            ->assertSee('Recent Chats')
            ->assertSee('Forklift Safety Reminder')
            ->assertSee('new')
            ->assertDontSee('Canteen Menu');
    }
}

