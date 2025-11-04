<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtering_conversations_by_type(): void
    {
        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);
        $department = Department::factory()->create(['name' => 'Inbound Logistics']);

        $direct = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Direct Sync',
                'type' => 'direct',
            ]);

        $direct->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::factory()->for($direct)->for($manager, 'sender')->create([
            'body' => 'Direct message ping.',
        ]);

        $departmentConversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Dept Briefing',
                'type' => 'department',
                'department_id' => $department->id,
            ]);

        $department->members()->attach([
            $manager->id => ['role' => 'manager', 'is_primary' => false],
            $employee->id => ['role' => 'member', 'is_primary' => true],
        ]);

        $departmentConversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::factory()->for($departmentConversation)->for($manager, 'sender')->create([
            'body' => 'Department broadcast.',
        ]);

        $announcement = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Policy Update',
                'type' => 'announcement',
                'is_locked' => true,
            ]);

        $announcement->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
        ]);

        Message::factory()->for($announcement)->for($manager, 'sender')->create([
            'body' => 'Announcement details.',
        ]);

        $this->actingAs($manager);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Direct Sync')
            ->assertSee('Dept Briefing')
            ->assertSee('Policy Update')
            ->assertSee('Direct')
            ->assertSee('Department Broadcast')
            ->assertSee('Announcements');

        $this->get(route('messages.index', ['type' => 'department']))
            ->assertOk()
            ->assertSee('Dept Briefing')
            ->assertDontSee('Direct Sync')
            ->assertDontSee('Policy Update')
            ->assertSee('aria-current="page"', false)
            ->assertSee('type=department', false);

        $this->get(route('messages.index', ['type' => 'announcement']))
            ->assertOk()
            ->assertSee('Policy Update')
            ->assertDontSee('Direct Sync')
            ->assertSee('Replies Locked');
    }
}
