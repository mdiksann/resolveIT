<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketResolvedNotification;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

#[Fillable(['title', 'description', 'status', 'requester_id', 'assignee_id', 'category_id', 'priority_id', 'due_at', 'resolved_at', 'closed_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    public function availableTransitions(): array
    {
        return match ($this->status) {
            TicketStatus::Open => [TicketStatus::Assigned, TicketStatus::InProgress],
            TicketStatus::Assigned => [TicketStatus::InProgress],
            TicketStatus::InProgress => [TicketStatus::Resolved],
            TicketStatus::Resolved => [TicketStatus::InProgress, TicketStatus::Closed],
            TicketStatus::Closed => [],
        };
    }

    public function transitionTo(TicketStatus $target, User $actor): void
    {
        DB::transaction(function () use ($target, $actor): void {
            $ticket = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            if (! in_array($target, $ticket->availableTransitions(), true)) {
                throw ValidationException::withMessages(['status' => 'This status transition is not allowed. Refresh the ticket and try again.']);
            }
            Gate::forUser($actor)->authorize('transitionStatus', [$ticket, $target]);
            $from = $ticket->status;
            $ticket->applyStatus($target);
            $ticket->save();
            TicketActivity::record($ticket, TicketEvent::StatusChanged, $actor, ['from' => $from->value, 'to' => $target->value]);
            if ($target === TicketStatus::Resolved) {
                $ticket->requester?->notify(new TicketResolvedNotification($ticket));
            }
        });
        $this->refresh();
    }

    private function applyStatus(TicketStatus $target, bool $unassigning = false): void
    {
        $from = $this->status;
        $unassignAllowed = $unassigning && $target === TicketStatus::Open && in_array($from, [TicketStatus::Open, TicketStatus::Assigned], true);
        if (! $unassignAllowed && ! in_array($target, $this->availableTransitions(), true)) {
            throw ValidationException::withMessages(['status' => 'This status transition is not allowed.']);
        }
        $this->status = $target;
        if ($target === TicketStatus::Resolved) {
            // Each entry stamps its own resolution; reopening clears the previous entry.
            $this->resolved_at = now();
        }
        if ($target === TicketStatus::Closed) {
            $this->closed_at = now();
        }
        if ($from === TicketStatus::Resolved && $target === TicketStatus::InProgress) {
            $this->resolved_at = null;
            $this->closed_at = null;
        }
    }

    public function assignTo(?User $assignee, User $actor): void
    {
        DB::transaction(function () use ($assignee, $actor): void {
            $ticket = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('assign', $ticket);
            if ($assignee) {
                $assignee = User::whereKey($assignee->id)->lockForUpdate()->firstOrFail();
                if (! in_array($assignee->role, [Role::Agent, Role::Admin], true)) {
                    throw ValidationException::withMessages(['assignee_id' => 'Choose an agent or administrator.']);
                }
            } elseif (! in_array($ticket->status, [TicketStatus::Open, TicketStatus::Assigned], true)) {
                throw ValidationException::withMessages(['assignee_id' => 'Only open or assigned tickets can be unassigned.']);
            }
            $from = $ticket->assignee_id;
            $fromName = $ticket->assignee?->name;
            $ticket->assignee_id = $assignee?->id;
            if (! $assignee) {
                $ticket->applyStatus(TicketStatus::Open, true);
            } elseif ($ticket->status === TicketStatus::Open) {
                $ticket->applyStatus(TicketStatus::Assigned);
            }
            $ticket->save();
            TicketActivity::record($ticket, $assignee ? TicketEvent::Assigned : TicketEvent::Unassigned, $actor, ['from' => $from, 'to' => $assignee?->id, 'from_name' => $fromName, 'to_name' => $assignee?->name]);
            if ($assignee) {
                $assignee->notify(new TicketAssignedNotification($ticket));
            }
        });
        $this->refresh();
    }

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }
}
