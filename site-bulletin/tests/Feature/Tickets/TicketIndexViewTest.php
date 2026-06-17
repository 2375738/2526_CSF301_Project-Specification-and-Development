<?php

namespace Tests\Feature\Tickets;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketIndexViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_index_shows_primary_report_issue_cta(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->actingAs($user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Report issue')
            ->assertSee(route('tickets.create'), false);
    }

    public function test_employee_ticket_index_shows_self_service_tabs(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'waiting_employee',
            'title' => 'Need badge number confirmation',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'in_progress',
            'title' => 'Printer repair underway',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'new',
            'title' => 'Locker door reported',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'closed',
            'title' => 'Old completed issue',
        ]);

        $this->actingAs($employee)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSeeText('My Ticket Flow')
            ->assertSeeText('Needs me')
            ->assertSeeText('In progress')
            ->assertSeeText('Waiting on team')
            ->assertSeeText('Resolved');
    }

    public function test_employee_needs_me_tab_filters_to_action_required_tickets(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'waiting_employee',
            'title' => 'Need badge number confirmation',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'resolved',
            'title' => 'Confirm scanner fix',
        ]);

        Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'status' => 'in_progress',
            'title' => 'Printer repair underway',
        ]);

        $this->actingAs($employee)
            ->get(route('tickets.index', ['queue' => 'needs_me']))
            ->assertOk()
            ->assertSeeText('Need badge number confirmation')
            ->assertSeeText('Confirm scanner fix')
            ->assertDontSeeText('Printer repair underway');
    }

    public function test_manager_ticket_index_does_not_show_employee_self_service_tabs(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSeeText('My Ticket Flow');
    }
}
