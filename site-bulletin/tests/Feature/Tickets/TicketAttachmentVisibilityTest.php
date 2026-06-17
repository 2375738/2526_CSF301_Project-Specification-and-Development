<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_upload_internal_timesheet_evidence(): void
    {
        Storage::fake('attachments');

        $department = Department::factory()->create(['name' => 'Support']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);
        $manager->departments()->attach($department->id, ['role' => 'manager', 'is_primary' => true]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::New,
        ]);

        $this->actingAs($manager)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('clock-proof.pdf', 128, 'application/pdf'),
                'kind' => 'timesheet',
                'visibility' => 'internal',
                'label' => 'Shift timing evidence',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'kind' => 'timesheet',
            'visibility' => 'internal',
            'label' => 'Shift timing evidence',
        ]);
    }

    public function test_employee_upload_is_forced_to_public_visibility(): void
    {
        Storage::fake('attachments');

        $department = Department::factory()->create(['name' => 'Inbound']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::New,
        ]);

        $this->actingAs($employee)
            ->post(route('tickets.attachments.store', $ticket), [
                'attachment' => UploadedFile::fake()->create('hazard.png', 128, 'image/png'),
                'kind' => 'photo',
                'visibility' => 'internal',
                'label' => 'Floor hazard photo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'user_id' => $employee->id,
            'kind' => 'photo',
            'visibility' => 'public',
            'label' => 'Floor hazard photo',
        ]);
    }

    public function test_employee_cannot_download_internal_attachment(): void
    {
        Storage::fake('attachments');

        $department = Department::factory()->create(['name' => 'Support']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::New,
        ]);

        $attachment = TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'path' => 'tickets/' . $ticket->id . '/internal-proof.pdf',
            'disk' => 'attachments',
            'original_name' => 'internal-proof.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'kind' => 'document',
            'visibility' => 'internal',
            'label' => 'Internal review note',
        ]);

        Storage::disk('attachments')->put($attachment->path, 'proof');

        $this->actingAs($employee)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_employee_ticket_detail_hides_internal_attachments(): void
    {
        $department = Department::factory()->create(['name' => 'Support']);
        $manager = User::factory()->manager()->create([
            'primary_department_id' => $department->id,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'primary_department_id' => $department->id,
        ]);

        $ticket = Ticket::factory()->create([
            'requester_id' => $employee->id,
            'created_for_id' => $employee->id,
            'department_id' => $department->id,
            'status' => TicketStatus::New,
        ]);

        TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'original_name' => 'public-photo.png',
            'kind' => 'photo',
            'visibility' => 'public',
            'label' => 'Station photo',
        ]);

        TicketAttachment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'original_name' => 'private-note.pdf',
            'kind' => 'document',
            'visibility' => 'internal',
            'label' => 'Internal review note',
        ]);

        $this->actingAs($employee)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSeeText('Station photo')
            ->assertDontSeeText('Internal review note')
            ->assertDontSeeText('Internal only');
    }
}
