<?php

namespace App\Models;

use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use Database\Factories\TicketActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['ticket_id', 'actor_id', 'event', 'properties'])]
class TicketActivity extends Model
{
    /** @use HasFactory<TicketActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Ticket activities are append-only.');
        });
        static::deleting(function (): never {
            throw new LogicException('Ticket activities are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'event' => TicketEvent::class,
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function record(Ticket $ticket, TicketEvent $event, ?User $actor, array $properties = []): self
    {
        return static::create(['ticket_id' => $ticket->id, 'actor_id' => $actor?->id, 'event' => $event, 'properties' => $properties]);
    }

    public function summary(): string
    {
        // Unknown future events remain readable without invoking the strict enum cast.
        $event = TicketEvent::tryFrom($this->getAttributes()['event']);
        $properties = $this->properties ?? [];
        $from = $properties['from_name'] ?? 'None';
        $to = $properties['to_name'] ?? 'None';

        return match ($event) {
            TicketEvent::Created => 'Created ticket',
            TicketEvent::Updated => 'Updated '.implode(' and ', $properties['fields'] ?? ['ticket details']),
            TicketEvent::StatusChanged => 'Status: '.(TicketStatus::tryFrom($properties['from'] ?? '')?->label() ?? 'Unknown').' → '.(TicketStatus::tryFrom($properties['to'] ?? '')?->label() ?? 'Unknown'),
            TicketEvent::Assigned => 'Assignment: '.$from.' → '.$to,
            TicketEvent::Unassigned => 'Unassigned '.$from,
            TicketEvent::PriorityChanged => 'Priority: '.$from.' → '.$to,
            TicketEvent::CategoryChanged => 'Category: '.$from.' → '.$to,
            TicketEvent::Commented => ($properties['is_internal'] ?? false) ? 'Added internal note' : 'Added public comment',
            TicketEvent::AttachmentAdded => 'Added attachment: '.($properties['name'] ?? 'File'),
            TicketEvent::AttachmentRemoved => 'Removed attachment: '.($properties['name'] ?? 'File'),
            default => 'Ticket activity',
        };
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
