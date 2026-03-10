<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\ManagerRelationship;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_direct_conversation(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $target = User::factory()->manager()->create();

        $response = $this->actingAs($employee)->post(route('messages.store'), [
            'type' => 'direct',
            'recipients' => [$target->id],
            'body' => 'Can we chat about the schedule?',
        ]);

        $response->assertRedirect();

        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertSame('direct', $conversation->type);
        $this->assertTrue($conversation->participants()->where('users.id', $employee->id)->exists());
        $this->assertTrue($conversation->participants()->where('users.id', $target->id)->exists());

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $employee->id,
            'body' => 'Can we chat about the schedule?',
        ]);
    }

    public function test_employee_cannot_create_department_broadcast(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $department = \App\Models\Department::factory()->create();

        $response = $this->actingAs($employee)
            ->post(route('messages.store'), [
                'type' => 'department',
                'department_id' => $department->id,
                'body' => 'Invalid attempt',
            ]);

        $response->assertSessionHasErrors('recipients');
        $this->assertSame(0, Conversation::count());
    }

    public function test_employee_forged_department_request_becomes_direct(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create();
        $department = Department::factory()->create();

        $this->actingAs($employee)
            ->post(route('messages.store'), [
                'type' => 'department',
                'department_id' => $department->id,
                'recipients' => [$other->id],
                'body' => 'Hello anyway',
            ])
            ->assertRedirect();

        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        $this->assertSame('direct', $conversation->type);
        $this->assertTrue($conversation->participants()->where('users.id', $other->id)->exists());
    }

    public function test_employee_sees_routed_contact_shortcuts(): void
    {
        $supportDepartment = Department::factory()->create(['name' => 'Support']);
        $employee = User::factory()->create(['role' => 'employee']);
        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $supportManager = User::factory()->manager()->create(['name' => 'Support Sam']);
        $hr = User::factory()->create(['role' => 'hr', 'name' => 'HR Helen']);

        ManagerRelationship::create([
            'manager_id' => $employee->id,
            'reports_to_id' => $manager->id,
            'relationship_type' => 'direct',
        ]);

        $supportDepartment->members()->attach($supportManager->id, ['role' => 'manager', 'is_primary' => true]);

        $this->actingAs($employee)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSeeText('Quick Contact')
            ->assertSeeText('Message My Manager')
            ->assertSeeText('Manager Pat')
            ->assertSeeText('Ask Support')
            ->assertSeeText('Support Sam')
            ->assertSeeText('Escalate to HR')
            ->assertSeeText('HR Helen');
    }

    public function test_my_manager_shortcut_reuses_existing_direct_conversation(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $manager = User::factory()->manager()->create();

        ManagerRelationship::create([
            'manager_id' => $employee->id,
            'reports_to_id' => $manager->id,
            'relationship_type' => 'direct',
        ]);

        $conversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Existing manager thread',
                'type' => 'direct',
            ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        Message::factory()->for($conversation)->for($manager, 'sender')->create([
            'body' => 'Initial manager note.',
        ]);

        $this->actingAs($employee)
            ->post(route('messages.store'), [
                'shortcut' => 'my_manager',
                'body' => 'Need help on station 14.',
            ])
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertSame(1, Conversation::count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $employee->id,
            'body' => 'Need help on station 14.',
        ]);
    }

    public function test_forged_shortcut_request_without_available_recipient_returns_validation_error(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)
            ->from(route('messages.index'))
            ->post(route('messages.store'), [
                'shortcut' => 'my_manager',
                'body' => 'Need help but no manager is configured.',
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors('shortcut');

        $this->assertSame(0, Conversation::count());
    }

    public function test_direct_shortcut_reuses_older_unlocked_conversation_when_newer_thread_is_locked(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $manager = User::factory()->manager()->create();

        ManagerRelationship::create([
            'manager_id' => $employee->id,
            'reports_to_id' => $manager->id,
            'relationship_type' => 'direct',
        ]);

        $reusableConversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Reusable manager thread',
                'type' => 'direct',
                'is_locked' => false,
                'updated_at' => now()->subMinutes(10),
            ]);

        $reusableConversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        $lockedConversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Locked manager thread',
                'type' => 'direct',
                'is_locked' => true,
                'updated_at' => now(),
            ]);

        $lockedConversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        $this->actingAs($employee)
            ->post(route('messages.store'), [
                'shortcut' => 'my_manager',
                'body' => 'Need support on this shift.',
            ])
            ->assertRedirect(route('messages.show', $reusableConversation));

        $this->assertSame(2, Conversation::count());
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $reusableConversation->id,
            'sender_id' => $employee->id,
            'body' => 'Need support on this shift.',
        ]);
    }
}
