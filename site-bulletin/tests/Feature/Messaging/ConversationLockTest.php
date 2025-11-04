<?php

namespace Tests\Feature\Messaging;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_conversation_blocks_replying(): void
    {
        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);

        $conversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Locked Thread',
                'type' => 'direct',
                'is_locked' => true,
            ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::factory()->for($conversation)->for($manager, 'sender')->create([
            'body' => 'Initial announcement.',
        ]);

        $this->actingAs($employee)
            ->get(route('messages.show', $conversation))
            ->assertOk()
            ->assertSee('Replies are locked for this conversation', false)
            ->assertDontSee('<h2 class="text-lg font-semibold text-slate-900">Reply</h2>', false);

        $this->actingAs($employee)
            ->post(route('messages.messages.store', $conversation), ['body' => 'Trying to reply'])
            ->assertForbidden();
    }

    public function test_conversation_owner_can_toggle_lock_and_audit_entry_is_created(): void
    {
        $manager = User::factory()->manager()->create();
        $conversation = Conversation::factory()->for($manager, 'creator')->create([
            'is_locked' => false,
        ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
        ]);

        $this->actingAs($manager)
            ->patch(route('messages.lock', $conversation), ['lock' => 1])
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertTrue($conversation->fresh()->is_locked);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'conversation.locked',
            'auditable_type' => Conversation::class,
            'auditable_id' => $conversation->id,
        ]);
    }
}
