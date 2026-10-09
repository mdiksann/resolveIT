<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_conceals_foreign_employee_ticket_and_allows_staff(): void
    {
        $ticket = Ticket::factory()->create();
        $this->get('/tickets/'.$ticket->id)->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/tickets/'.$ticket->id)->assertNotFound();
        $this->get('/tickets/999999')->assertNotFound();
        foreach ([$ticket->requester, User::factory()->create(['role' => Role::Agent]), User::factory()->create(['role' => Role::Admin])] as $user) {
            $this->actingAs($user)->get('/tickets/'.$ticket->id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('Tickets/Show')->where('ticket.number', '#'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT))->where('ticket.requester.name', $ticket->requester->name)->where('ticket.description', $ticket->description)->missing('ticket.requester.email')->missing('ticket.requester.password'));
        }
    }

    public function test_detail_query_bound_and_nullable_metadata(): void
    {
        $ticket = Ticket::factory()->create(['category_id' => null]);
        $user = $ticket->requester;
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_starts_with($query->sql, 'select')) {
                $queries++;
            }
        });
        $this->actingAs($user)->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->where('ticket.category', null)->where('ticket.assignee', null)->where('can.assign', false));
        $this->assertLessThanOrEqual(14, $queries);
    }
}
