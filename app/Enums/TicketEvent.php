<?php

namespace App\Enums;

enum TicketEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case PriorityChanged = 'priority_changed';
    case CategoryChanged = 'category_changed';
    case Commented = 'commented';
    case AttachmentAdded = 'attachment_added';
    case AttachmentRemoved = 'attachment_removed';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::StatusChanged => 'Status changed',
            self::Assigned => 'Assigned',
            self::Unassigned => 'Unassigned',
            self::PriorityChanged => 'Priority changed',
            self::CategoryChanged => 'Category changed',
            self::Commented => 'Commented',
            self::AttachmentAdded => 'Attachment added',
            self::AttachmentRemoved => 'Attachment removed',
        };
    }
}
