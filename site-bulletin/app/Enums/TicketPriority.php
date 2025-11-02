<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function options(): array
    {
        return [
            self::Low->value => 'Low',
            self::Medium->value => 'Medium',
            self::High->value => 'High',
            self::Critical->value => 'Critical',
        ];
    }
}
