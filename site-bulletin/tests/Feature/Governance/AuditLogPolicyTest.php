<?php

namespace Tests\Feature\Governance;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\RoleChangeRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuditLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_audit_log_for_managed_ticket(): void
    {
        $department = Department::factory()->create();
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->create();

        $manager->departments()->attach($department->id, ['role' => 'manager']);
        $employee->departments()->attach($department->id, ['role' => 'member']);

        $ticket = Ticket::factory()->create(['department_id' => $department->id]);

        $log = AuditLog::factory()->create([
            'event_type' => 'ticket.status.updated',
            'auditable_type' => Ticket::class,
            'auditable_id' => $ticket->id,
        ]);

        $this->assertTrue(Gate::forUser($manager)->allows('view', $log));
        $this->assertFalse(Gate::forUser($employee)->allows('view', $log));
    }

    public function test_hr_can_view_any_audit_log(): void
    {
        $hr = User::factory()->create(['role' => 'hr']);
        $log = AuditLog::factory()->create();

        $this->assertTrue(Gate::forUser($hr)->allows('view', $log));
    }
}
