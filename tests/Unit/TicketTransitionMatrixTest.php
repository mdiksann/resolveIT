<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketTransitionMatrixTest extends TestCase
{
    #[DataProvider('matrix')]
    public function test_full_transition_map(TicketStatus $from, TicketStatus $to, bool $allowed): void
    {
        $ticket = new Ticket(['status' => $from]);
        $this->assertSame($allowed, in_array($to, $ticket->availableTransitions(), true));
    }

    public static function matrix(): array
    {
        $allowed = ['OPEN:ASSIGNED', 'OPEN:IN_PROGRESS', 'ASSIGNED:IN_PROGRESS', 'IN_PROGRESS:RESOLVED', 'RESOLVED:IN_PROGRESS', 'RESOLVED:CLOSED'];
        $cases = [];
        foreach (TicketStatus::cases() as $from) {
            foreach (TicketStatus::cases() as $to) {
                $cases[] = [$from, $to, in_array($from->value.':'.$to->value, $allowed, true)];
            }
        }

        return $cases;
    }
}
