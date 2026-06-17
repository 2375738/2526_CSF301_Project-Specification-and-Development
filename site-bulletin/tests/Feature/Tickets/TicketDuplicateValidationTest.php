<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_cannot_be_marked_duplicate_of_itself(): void
    {
        $manager = User::factory()->manager()->create();
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::New,
        ]);

        $response = $this
            ->actingAs($manager)
            ->patch(route('tickets.status.update', $ticket), [
                'status' => TicketStatus::Cancelled->value,
                'duplicate_of_id' => $ticket->id,
            ]);

        $response->assertSessionHasErrors('duplicate_of_id');
        $this->assertNull($ticket->fresh()->duplicate_of_id);
    }

    public function test_duplicate_target_must_be_in_same_department_when_departments_are_set(): void
    {
        $manager = User::factory()->manager()->create();
        $firstDepartment = Department::factory()->create();
        $secondDepartment = Department::factory()->create();
        $ticket = Ticket::factory()->create([
            'department_id' => $firstDepartment->id,
            'status' => TicketStatus::New,
        ]);
        $primary = Ticket::factory()->create([
            'department_id' => $secondDepartment->id,
            'status' => TicketStatus::New,
        ]);

        $response = $this
            ->actingAs($manager)
            ->patch(route('tickets.status.update', $ticket), [
                'status' => TicketStatus::Cancelled->value,
                'duplicate_of_id' => $primary->id,
            ]);

        $response->assertSessionHasErrors('duplicate_of_id');
        $this->assertNull($ticket->fresh()->duplicate_of_id);
    }
}
