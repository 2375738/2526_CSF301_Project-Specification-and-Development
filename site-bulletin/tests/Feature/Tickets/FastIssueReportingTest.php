<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FastIssueReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_fast_report_presets(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);

        Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);

        $this->actingAs($employee)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSeeText('Ticket Templates')
            ->assertSeeText('Scanner issue')
            ->assertSeeText('Safety concern')
            ->assertSeeText('Facilities issue')
            ->assertSeeText('Transport problem')
            ->assertDontSeeText('Department blocker');
    }

    public function test_scanner_template_prefills_ticket_form(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $itSupport = Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);
        Category::factory()->create(['name' => 'HR', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);

        $this->actingAs($employee)
            ->get(route('tickets.create', ['template' => 'scanner_issue']))
            ->assertOk()
            ->assertSeeText('Selected')
            ->assertSee('value="Scanner issue at station"', false)
            ->assertSeeText('Template loaded. Edit the starter text if you need more detail.')
            ->assertSee('Scanner problem observed. Device is not working as expected and is blocking task progress.', false)
            ->assertSeeText('Asset tag or scanner ID')
            ->assertSee('value="' . $itSupport->id . '" selected', false);
    }

    public function test_manager_sees_manager_only_templates(): void
    {
        $manager = User::factory()->manager()->create();

        Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);
        Category::factory()->create(['name' => 'HR', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);

        $this->actingAs($manager)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSeeText('Report on behalf')
            ->assertSeeText('Department blocker');
    }

    public function test_template_sets_default_priority_when_ticket_is_created(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        $safety = Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);
        Category::factory()->create(['name' => 'HR', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);

        $this->actingAs($employee)
            ->post(route('tickets.store'), [
                'template' => 'safety_hazard',
                'category_id' => $safety->id,
                'title' => 'Safety concern in work area',
                'description' => 'Safety concern observed. Immediate risk and impact need review.',
                'location' => 'Dock A1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'requester_id' => $employee->id,
            'category_id' => $safety->id,
            'template_key' => 'safety_hazard',
            'priority' => 'high',
            'title' => 'Safety concern in work area',
        ]);
    }

    public function test_template_detail_answers_are_stored_on_ticket(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $itSupport = Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);
        Category::factory()->create(['name' => 'HR', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Operations', 'audience' => 'all']);

        $this->actingAs($employee)
            ->post(route('tickets.store'), [
                'template' => 'scanner_issue',
                'category_id' => $itSupport->id,
                'title' => 'Scanner issue at station',
                'description' => 'Scanner problem observed. Device is not working as expected and is blocking task progress.',
                'location' => 'Station A3',
                'detail_answers' => [
                    'SCN-14',
                    'Drops connection every 10 minutes',
                    'Yes, picking is blocked',
                ],
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();

        $this->assertNotNull($ticket);
        $this->assertSame('scanner_issue', $ticket->template_key);
        $this->assertSame([
            ['label' => 'Asset tag or scanner ID', 'value' => 'SCN-14'],
            ['label' => 'What the device is doing now', 'value' => 'Drops connection every 10 minutes'],
            ['label' => 'Whether work is fully blocked', 'value' => 'Yes, picking is blocked'],
        ], $ticket->details_json);
    }
}
