<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_filter_tickets_by_breach_date_range_and_type(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);

        $safety = Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        $hr = Category::factory()->create(['name' => 'HR', 'audience' => 'all']);

        $matching = Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $safety->id,
            'status' => 'in_progress',
            'title' => 'Matching breached safety ticket',
            'sla_first_response_breached' => true,
            'updated_at' => now()->subDay(),
        ]);

        Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $safety->id,
            'status' => 'in_progress',
            'title' => 'Safety but not breached',
            'sla_first_response_breached' => false,
            'sla_resolution_breached' => false,
            'updated_at' => now()->subDay(),
        ]);

        Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $hr->id,
            'status' => 'in_progress',
            'title' => 'Breached HR ticket',
            'sla_resolution_breached' => true,
            'updated_at' => now()->subDay(),
        ]);

        Ticket::factory()->create([
            'department_id' => $department->id,
            'category_id' => $safety->id,
            'status' => 'in_progress',
            'title' => 'Breached but out of range',
            'sla_resolution_breached' => true,
            'updated_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($manager)->get(route('tickets.index', [
            'department_id' => $department->id,
            'category_id' => $safety->id,
            'breached' => 1,
            'from_date' => now()->subDays(2)->toDateString(),
            'to_date' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSeeText($matching->title)
            ->assertDontSeeText('Safety but not breached')
            ->assertDontSeeText('Breached HR ticket')
            ->assertDontSeeText('Breached but out of range');
    }
}

