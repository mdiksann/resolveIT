<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'OPEN';
    case Assigned = 'ASSIGNED';
    case InProgress = 'IN_PROGRESS';
    case Resolved = 'RESOLVED';
    case Closed = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }
}
