<?php

namespace Tests\Feature\Automation;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SlaAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SlaAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_response_breach_creates_announcement(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create();
        $actor = User::factory()->manager()->create();

        $manager->departments()->attach($department->id, ['role' => 'manager']);
        $actor->departments()->attach($department->id, ['role' => 'manager']);

        $ticket = Ticket::factory()->create([
            'department_id' => $department->id,
            'sla_first_response_breached' => true,
            'notified_first_response_breach' => false,
        ]);

        $auditLogger = Mockery::mock(AuditLogger::class);
        $auditLogger->shouldReceive('log')->once();

        $service = new SlaAutomationService($auditLogger);

        $service->handleFirstResponseBreach($ticket->fresh(), $actor);

        $ticket->refresh();

        $this->assertTrue($ticket->notified_first_response_breach);
        $this->assertDatabaseHas('conversations', [
            'subject' => 'SLA First response breach · Ticket #' . $ticket->id,
            'department_id' => $department->id,
        ]);

        $conversation = Conversation::where('subject', 'like', 'SLA First%')->first();
        $this->assertNotNull($conversation);
        $this->assertGreaterThan(0, $conversation->messages()->count());
    }
}
