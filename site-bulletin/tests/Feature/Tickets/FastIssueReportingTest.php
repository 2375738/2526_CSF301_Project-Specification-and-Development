<?php

namespace Tests\Feature\Tickets;

use App\Models\Category;
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
            ->assertSeeText('Fast Report')
            ->assertSeeText('Scanner issue')
            ->assertSeeText('Safety concern')
            ->assertSeeText('Facilities issue')
            ->assertSeeText('Transport problem');
    }

    public function test_scanner_preset_prefills_fast_report_form(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $itSupport = Category::factory()->create(['name' => 'IT Support', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Safety', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Facilities', 'audience' => 'all']);
        Category::factory()->create(['name' => 'Transport', 'audience' => 'all']);

        $this->actingAs($employee)
            ->get(route('tickets.create', ['preset' => 'scanner']))
            ->assertOk()
            ->assertSeeText('Selected')
            ->assertSee('value="Scanner issue at station"', false)
            ->assertSeeText('Fast report loaded. Edit the starter text if you need more detail.')
            ->assertSee('Scanner problem observed. Device is not working as expected and is blocking task progress.', false)
            ->assertSee('value="' . $itSupport->id . '" selected', false);
    }
}
