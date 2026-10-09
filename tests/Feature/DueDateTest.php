<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DueDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_overdue_flags_follow_status_and_strict_due_boundary(): void
    {
        $this->freezeSecond();
        config(['app.timezone' => 'Asia/Jakarta']);
        $agent = User::factory()->create(['role' => Role::Agent]);
        foreach (TicketStatus::cases() as $status) {
            foreach ([-1, 0, 1] as $offset) {
                $ticket = Ticket::factory()->create(['status' => $status, 'due_at' => now()->addSeconds($offset)]);
                $expected = $offset < 0 && ! in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true);
                $this->actingAs($agent)->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p
                    ->where('ticket.overdue', $expected)->where('timeZone', 'Asia/Jakarta')->where('generatedAt', now()->toISOString()));
                $this->get('/tickets?search='.urlencode($ticket->title))->assertInertia(fn (Assert $p) => $p
                    ->where('tickets.data.0.overdue', $expected)->where('generatedAt', now()->toISOString()));
            }
        }
    }
}
