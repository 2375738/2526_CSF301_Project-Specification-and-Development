<?php

namespace Tests\Feature\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActionableNotificationDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_dropdown_shows_next_action_label(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id, 'title' => 'Scanner battery swap needed']);

        $this->storeNotification($user, [
            'type' => 'ticket',
            'title' => 'Ticket #' . $ticket->id . ': Scanner battery swap needed',
            'message' => 'Ticket #' . $ticket->id . ' was resolved by Area Manager',
            'url' => route('tickets.show', $ticket),
            'action_label' => 'Review fix',
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Action needed')
            ->assertSeeText('Review fix')
            ->assertSeeText('Ticket #' . $ticket->id . ': Scanner battery swap needed');
    }

    public function test_marking_notification_read_redirects_to_action_target(): void
    {
        $user = User::factory()->create(['role' => 'employee']);
        $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
        $id = $this->storeNotification($user, [
            'type' => 'ticket',
            'title' => 'Ticket updated',
            'message' => 'A ticket update is ready.',
            'url' => route('tickets.show', $ticket),
            'action_label' => 'Open ticket',
        ]);

        $this->actingAs($user)
            ->patch(route('notifications.read', $id))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertNotNull($user->notifications()->findOrFail($id)->read_at);
    }

    protected function storeNotification(User $user, array $data): string
    {
        $id = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
