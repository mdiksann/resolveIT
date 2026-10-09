<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TicketPolicyDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_laravel_discovers_the_ticket_policy_and_gates_use_it(): void
    {
        $this->assertInstanceOf(TicketPolicy::class, Gate::getPolicyFor(Ticket::class));
        $ticket = Ticket::factory()->create();
        $employee = $ticket->requester;
        $this->assertTrue($employee->can('view', $ticket));
        $this->assertFalse($employee->can('viewInternalNotes', $ticket));
        $this->assertFalse($employee->can('manage-tickets'));
        $this->assertTrue(User::factory()->create(['role' => Role::Agent])->can('manage-tickets'));
    }
}
