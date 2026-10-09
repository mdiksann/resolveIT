<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use PHPUnit\Framework\TestCase;

class DomainEnumTest extends TestCase
{
    public function test_role_values_and_labels(): void
    {
        $this->assertSame(['EMPLOYEE', 'AGENT', 'ADMIN'], array_column(Role::cases(), 'value'));
        $this->assertSame(['Employee', 'Agent', 'Administrator'], array_map(fn (Role $role) => $role->label(), Role::cases()));
        $this->assertNull(Role::tryFrom('USER'));
    }

    public function test_ticket_status_values_and_labels(): void
    {
        $this->assertSame(['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'], array_column(TicketStatus::cases(), 'value'));
        $this->assertSame(['Open', 'Assigned', 'In Progress', 'Resolved', 'Closed'], array_map(fn (TicketStatus $status) => $status->label(), TicketStatus::cases()));
    }

    public function test_ticket_event_values_and_labels(): void
    {
        $this->assertSame(['created', 'status_changed', 'assigned', 'unassigned', 'priority_changed', 'category_changed', 'commented', 'attachment_added', 'attachment_removed'], array_column(TicketEvent::cases(), 'value'));
        $this->assertSame(['Created', 'Status changed', 'Assigned', 'Unassigned', 'Priority changed', 'Category changed', 'Commented', 'Attachment added', 'Attachment removed'], array_map(fn (TicketEvent $event) => $event->label(), TicketEvent::cases()));
    }
}
