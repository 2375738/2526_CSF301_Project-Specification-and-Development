<?php

namespace Tests\Feature\Tickets;

use App\Models\User;
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
}
