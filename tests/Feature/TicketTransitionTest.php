<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pairs_at_endpoint_and_one_activity_for_legal_entries(): void
    {
        foreach ([Role::Agent, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (TicketStatus::cases() as $from) {
                foreach (TicketStatus::cases() as $to) {
                    $ticket = Ticket::factory()->create(['status' => $from]);
                    $response = $this->patchJson('/tickets/'.$ticket->id.'/status', ['status' => $to->value]);
                    if (in_array($to, $ticket->availableTransitions(), true)) {
                        $response->assertRedirect();
                        $this->assertSame($to, $ticket->fresh()->status);
                        $this->assertSame(TicketEvent::StatusChanged, $ticket->activities()->sole()->event);
                        $this->assertEquals(['from' => $from->value, 'to' => $to->value], $ticket->activities()->sole()->properties);
                    } else {
                        $response->assertUnprocessable()->assertJsonValidationErrors('status');
                        $this->assertSame($from, $ticket->fresh()->status);
                        $this->assertSame(0, $ticket->activities()->count());
                    }
                }
            }
        }
    }

    public function test_employee_only_reopens_or_closes_own_resolved_ticket(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        foreach ([TicketStatus::InProgress, TicketStatus::Closed] as $status) {
            $ticket = Ticket::factory()->create(['requester_id' => $owner->id, 'status' => TicketStatus::Resolved]);
            $this->patch('/tickets/'.$ticket->id.'/status', ['status' => $status->value])->assertRedirect();
            $this->assertSame($status, $ticket->fresh()->status);
        }
        $ticket = Ticket::factory()->create(['requester_id' => $owner->id]);
        $this->patchJson('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::InProgress->value])->assertForbidden();
        $foreign = Ticket::factory()->create(['status' => TicketStatus::Resolved]);
        $this->patchJson('/tickets/'.$foreign->id.'/status', ['status' => TicketStatus::Closed->value])->assertForbidden();
        $this->assertSame(TicketStatus::Resolved, $foreign->fresh()->status);
    }

    public function test_entry_timestamps_reopen_and_second_resolution(): void
    {
        $this->freezeSecond();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);
        $this->actingAs($agent);
        $this->patch('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::Resolved->value]);
        $first = $ticket->fresh()->resolved_at;
        $this->assertTrue($first->equalTo(now()));
        $this->travel(2)->hours();
        $this->patchJson('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::Resolved->value])->assertUnprocessable();
        $this->assertTrue($ticket->fresh()->resolved_at->equalTo($first));
        $this->patch('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::InProgress->value]);
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->assertNull($ticket->fresh()->closed_at);
        $this->patch('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::Resolved->value]);
        $this->assertTrue($ticket->fresh()->resolved_at->equalTo(now()));
        $this->patch('/tickets/'.$ticket->id.'/status', ['status' => TicketStatus::Closed->value]);
        $this->assertTrue($ticket->fresh()->closed_at->equalTo(now()));
        $this->assertTrue($ticket->fresh()->resolved_at->equalTo(now()));
    }

    public function test_invalid_fields_guests_and_concurrent_stale_model(): void
    {
        $ticket = Ticket::factory()->create();
        $this->patch('/tickets/'.$ticket->id.'/status', [])->assertRedirect('/login');
        $agent = User::factory()->create(['role' => Role::Agent]);
        $this->actingAs($agent);
        foreach ([['status' => 'BAD'], ['status' => TicketStatus::InProgress->value, 'reason' => 'Unknown']] as $data) {
            $this->patchJson('/tickets/'.$ticket->id.'/status', $data)->assertUnprocessable();
        }
        $stale = $ticket->replicate();
        $stale->id = $ticket->id;
        $ticket->transitionTo(TicketStatus::InProgress, $agent);
        $stale->transitionTo(TicketStatus::Resolved, $agent);
        $this->assertSame(TicketStatus::Resolved, $ticket->fresh()->status);
    }
}
