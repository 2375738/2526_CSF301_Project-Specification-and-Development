<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\NewAnnouncement;

class AnnouncementObserver
{
    public function created(Announcement $announcement): void
    {
        if (!$announcement->is_active) {
            return;
        }

        $query = User::query();

        if ($announcement->audience === 'department' && $announcement->department_id) {
            $query->whereHas('departments', function ($q) use ($announcement) {
                $q->where('departments.id', $announcement->department_id);
            });
        } elseif ($announcement->audience === 'managers') {
            $query->where('role', 'manager')
                  ->orWhere('role', 'admin')
                  ->orWhere('role', 'hr');
        }
        // If 'all', we don't filter, so we get all users.

        // Chunking to avoid memory issues with large user bases, though for this scale it's fine.
        $query->chunk(100, function ($users) use ($announcement) {
            foreach ($users as $user) {
                // Don't notify the author? Maybe they want to see it too, but usually not.
                if ($user->id !== $announcement->author_id && $this->canReceiveAnnouncementNotifications($user)) {
                    $user->notify(new NewAnnouncement($announcement));
                }
            }
        });
    }

    protected function canReceiveAnnouncementNotifications(User $user): bool
    {
        return (bool) ($user->email_notifications_enabled ?? true);
    }
}
