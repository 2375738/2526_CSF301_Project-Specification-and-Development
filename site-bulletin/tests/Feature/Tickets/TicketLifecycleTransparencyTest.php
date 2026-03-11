<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLifecycleTransparencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_sees_plain_language_lifecycle_summary_for_waiting_ticket(): void
    {
        $department = Department::factory()->create(['name' => 'Inbound']);
        $category = Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);
        $requester = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);

        $ticket = Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $category->id,
            'requester_id' => $requester->id,
            'assignee_id' => $manager->id,
            'template_key' => 'scanner_issue',
            'priority' => TicketPriority::High,
            'status' => TicketStatus::WaitingEmployee,
            'title' => 'Scanner battery swap needed',
            'description' => 'Scanner 14 is dropping connection after 10 minutes.',
            'details_json' => [
                ['label' => 'Asset tag or scanner ID', 'value' => 'SCN-14'],
                ['label' => 'Whether work is fully blocked', 'value' => 'Yes, active work is blocked'],
            ],
            'sla_resolution_breached' => true,
        ]);

        TicketStatusChange::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'from_status' => TicketStatus::InProgress,
            'to_status' => TicketStatus::WaitingEmployee,
            'reason' => 'Need the scanner asset tag to arrange the replacement.',
        ]);

        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'body' => 'Please send the asset tag from the back of the scanner.',
            'is_private' => false,
        ]);

        TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'body' => 'Private escalation note for maintenance.',
            'is_private' => true,
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('What This Means')
            ->assertSeeText('The support team is waiting for something from you before work can continue.')
            ->assertSeeText('Who is handling this')
            ->assertSeeText('Waiting on you')
            ->assertSeeText('What happens next')
            ->assertSeeText('Add the missing detail or confirm the next step so work can continue.')
            ->assertSeeText('Template:')
            ->assertSeeText('Scanner issue')
            ->assertSeeText('Structured Details')
            ->assertSeeText('Asset tag or scanner ID')
            ->assertSeeText('SCN-14')
            ->assertSeeText('Latest visible update')
            ->assertSeeText('Service note')
            ->assertSeeText('This ticket has missed a service target and should be treated as urgent.')
            ->assertSeeText('Action needed from you')
            ->assertSeeText('Waiting for requester response')
            ->assertSeeText('Please send the asset tag from the back of the scanner.')
            ->assertDontSeeText('Private escalation note for maintenance.');
    }

    public function test_requester_sees_resolution_guidance_when_fix_is_marked_complete(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $category = Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        $requester = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $ticket = Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $category->id,
            'requester_id' => $requester->id,
            'status' => TicketStatus::Resolved,
            'title' => 'Badge access reset',
            'description' => 'Badge stopped opening the side entrance.',
        ]);

        $this->actingAs($requester)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('A fix has been marked as complete and is waiting for your confirmation.')
            ->assertSeeText('Review the fix')
            ->assertSeeText('Confirm & Close')
            ->assertSeeText('Reopen Ticket');
    }
}
