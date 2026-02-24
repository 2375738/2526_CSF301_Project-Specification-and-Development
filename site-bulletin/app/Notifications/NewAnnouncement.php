<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAnnouncement extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Announcement: ' . $this->announcement->title,
            'message' => \Illuminate\Support\Str::limit($this->announcement->body, 50),
            'url' => route('dashboard'), // Or a specific announcement route if one exists
            'type' => 'announcement',
            'author_id' => $this->announcement->author_id,
        ];
    }
}
