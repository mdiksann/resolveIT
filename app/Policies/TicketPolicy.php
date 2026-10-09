<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Agent, Role::Admin], true);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->viewAny($user) || $user->id === $ticket->requester_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, Role::cases(), true);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($this->viewAny($user)) {
            return $ticket->status !== TicketStatus::Closed;
        }

        return $user->id === $ticket->requester_id && in_array($ticket->status, [TicketStatus::Open, TicketStatus::Assigned], true);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $this->viewAny($user) && $ticket->status !== TicketStatus::Closed;
    }

    public function transitionStatus(User $user, Ticket $ticket, TicketStatus $target): bool
    {
        if (! $this->viewAny($user)) {
            return $this->view($user, $ticket)
                && $ticket->status === TicketStatus::Resolved
                && in_array($target, [TicketStatus::InProgress, TicketStatus::Closed], true);
        }

        return in_array($target, $ticket->availableTransitions(), true);
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket) && $ticket->status !== TicketStatus::Closed;
    }

    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $this->viewAny($user);
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $this->viewInternalNotes($user, $ticket) && $this->comment($user, $ticket);
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket);
    }

    public function deleteAttachment(User $user, Ticket $ticket, TicketAttachment $attachment): bool
    {
        return $attachment->ticket_id === $ticket->id
            && $this->attach($user, $ticket)
            && ($user->role === Role::Admin || $attachment->uploader_id === $user->id);
    }

    public function viewActivity(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
