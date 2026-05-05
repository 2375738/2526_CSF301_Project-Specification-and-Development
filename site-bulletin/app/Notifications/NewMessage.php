<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Message',
            'message' => 'From ' . $this->message->sender->name . ': ' . \Illuminate\Support\Str::limit($this->message->body, 50),
            'url' => route('messages.show', $this->message->conversation_id),
            'type' => 'message',
            'action_label' => 'Reply',
            'sender_id' => $this->message->sender_id,
        ];
    }
}
