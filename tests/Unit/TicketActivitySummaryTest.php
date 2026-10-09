<?php

namespace Tests\Unit;

use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\TicketActivity;
use PHPUnit\Framework\TestCase;

class TicketActivitySummaryTest extends TestCase
{
    public function test_all_event_summaries_and_future_fallback(): void
    {
        $cases = [
            [TicketEvent::Created, [], 'Created ticket'],
            [TicketEvent::Updated, ['fields' => ['title', 'description']], 'Updated title and description'],
            [TicketEvent::StatusChanged, ['from' => TicketStatus::Open->value, 'to' => TicketStatus::Assigned->value], 'Status: Open → Assigned'],
            [TicketEvent::Assigned, ['from_name' => 'Alex', 'to_name' => 'Sam'], 'Assignment: Alex → Sam'],
            [TicketEvent::Unassigned, ['from_name' => 'Alex'], 'Unassigned Alex'],
            [TicketEvent::PriorityChanged, ['from_name' => 'Low', 'to_name' => 'High'], 'Priority: Low → High'],
            [TicketEvent::CategoryChanged, ['from_name' => null, 'to_name' => 'VPN'], 'Category: None → VPN'],
            [TicketEvent::Commented, ['is_internal' => false], 'Added public comment'],
            [TicketEvent::Commented, ['is_internal' => true], 'Added internal note'],
            [TicketEvent::AttachmentAdded, ['name' => 'log.txt'], 'Added attachment: log.txt'],
            [TicketEvent::AttachmentRemoved, ['name' => 'log.txt'], 'Removed attachment: log.txt'],
        ];
        foreach ($cases as [$event, $properties, $expected]) {
            $this->assertSame($expected, (new TicketActivity(['event' => $event, 'properties' => $properties]))->summary());
        }
        $future = new TicketActivity;
        $future->setRawAttributes(['event' => 'future_event', 'properties' => '{}']);
        $this->assertSame('Ticket activity', $future->summary());
    }
}
