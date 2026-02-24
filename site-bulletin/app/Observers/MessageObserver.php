<?php

namespace App\Observers;

use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;

class MessageObserver
{
    public function created(Message $message): void
    {
        if ($message->is_system) {
            return;
        }

        $conversation = $message->conversation;
        
        // Notify all participants except the sender
        foreach ($conversation->participants as $participant) {
            if ($participant->id !== $message->sender_id && $this->canReceiveMessageNotifications($participant)) {
                $participant->notify(new NewMessage($message));
            }
        }
    }

    protected function canReceiveMessageNotifications(User $user): bool
    {
        return (bool) ($user->email_notifications_enabled ?? true);
    }
}
