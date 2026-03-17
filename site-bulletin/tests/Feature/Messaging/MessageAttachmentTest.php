<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_send_attachment_only_reply(): void
    {
        Storage::fake('attachments');

        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);

        $conversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Attachment Thread',
                'type' => 'direct',
            ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        $this->actingAs($employee)
            ->post(route('messages.messages.store', $conversation), [
                'body' => '',
                'attachments' => [
                    UploadedFile::fake()->create('station-photo.png', 128, 'image/png'),
                ],
            ])
            ->assertRedirect(route('messages.show', $conversation));

        $message = Message::query()->latest('id')->first();

        $this->assertNotNull($message);
        $this->assertNull($message->body);
        $this->assertDatabaseHas('message_attachments', [
            'message_id' => $message->id,
            'user_id' => $employee->id,
            'original_name' => 'station-photo.png',
        ]);
    }

    public function test_participant_can_download_message_attachment(): void
    {
        Storage::fake('attachments');

        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);

        $conversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Attachment Download',
                'type' => 'direct',
            ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        $message = Message::factory()->for($conversation)->for($manager, 'sender')->create([
            'body' => 'See attached.',
        ]);

        $attachment = MessageAttachment::create([
            'message_id' => $message->id,
            'user_id' => $manager->id,
            'path' => 'messages/' . $conversation->id . '/' . $message->id . '/brief.pdf',
            'disk' => 'attachments',
            'original_name' => 'brief.pdf',
            'mime' => 'application/pdf',
            'size' => 2048,
        ]);

        Storage::disk('attachments')->put($attachment->path, 'brief-content');

        $this->actingAs($employee)
            ->get(route('messages.attachments.download', $attachment))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=brief.pdf');
    }

    public function test_participant_can_preview_image_attachment_inline(): void
    {
        Storage::fake('attachments');

        $manager = User::factory()->manager()->create(['name' => 'Manager Pat']);
        $employee = User::factory()->create(['name' => 'Employee Lee']);

        $conversation = Conversation::factory()
            ->for($manager, 'creator')
            ->create([
                'subject' => 'Image Preview',
                'type' => 'direct',
            ]);

        $conversation->participants()->sync([
            $manager->id => ['role' => 'owner', 'last_read_at' => now()],
            $employee->id => ['role' => 'member', 'last_read_at' => now()],
        ]);

        $message = Message::factory()->for($conversation)->for($manager, 'sender')->create([
            'body' => null,
        ]);

        $attachment = MessageAttachment::create([
            'message_id' => $message->id,
            'user_id' => $manager->id,
            'path' => 'messages/' . $conversation->id . '/' . $message->id . '/preview.png',
            'disk' => 'attachments',
            'original_name' => 'preview.png',
            'mime' => 'image/png',
            'size' => 1024,
        ]);

        Storage::disk('attachments')->put($attachment->path, 'fake-image-binary');

        $this->actingAs($employee)
            ->get(route('messages.attachments.preview', $attachment))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="preview.png"');
    }
}
