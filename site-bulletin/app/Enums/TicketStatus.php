<?php

namespace App\Enums;

enum TicketStatus: string
{
    case New = 'new';
    case Triaged = 'triaged';
    case InProgress = 'in_progress';
    case WaitingEmployee = 'waiting_employee';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';

    public static function open(): array
    {
        return [
            self::New,
            self::Triaged,
            self::InProgress,
            self::WaitingEmployee,
            self::Reopened,
        ];
    }
}
