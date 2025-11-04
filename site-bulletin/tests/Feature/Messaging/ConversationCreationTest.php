<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
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
        $department = \App\Models\Department::factory()->create();

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
}
