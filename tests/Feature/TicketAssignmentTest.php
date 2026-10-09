<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_endpoints_by_role_and_status_side_effects(): void
    {
        foreach (Role::cases() as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $agent = User::factory()->create(['role' => Role::Agent]);
            $ticket = Ticket::factory()->create(['requester_id' => $actor->id]);
            $this->actingAs($actor);
            $response = $this->patch('/tickets/'.$ticket->id.'/assignment', ['assignee_id' => $agent->id]);
            if ($role === Role::Employee) {
                $response->assertForbidden();
                $this->post('/tickets/'.$ticket->id.'/self-assign')->assertForbidden();
                $this->delete('/tickets/'.$ticket->id.'/assignment')->assertForbidden();
                $this->assertSame(0, $ticket->activities()->count());

                continue;
            }
            $response->assertRedirect();
            $this->assertSame(TicketStatus::Assigned, $ticket->fresh()->status);
            $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
            $this->assertSame(TicketEvent::Assigned, $ticket->activities()->sole()->event);
            $this->post('/tickets/'.$ticket->id.'/self-assign')->assertRedirect();
            $this->assertSame($actor->id, $ticket->fresh()->assignee_id);
            $this->assertSame(2, $ticket->activities()->count());
            $this->delete('/tickets/'.$ticket->id.'/assignment')->assertRedirect();
            $this->assertNull($ticket->fresh()->assignee_id);
            $this->assertSame(TicketStatus::Open, $ticket->fresh()->status);
            $this->assertSame(3, $ticket->activities()->count());
            $this->assertSame(TicketEvent::Unassigned, $ticket->activities()->orderByDesc('id')->first()->event);
        }
    }

    public function test_reassignment_does_not_change_work_status_and_unassignment_window(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin);
        foreach ([TicketStatus::Open, TicketStatus::Assigned, TicketStatus::InProgress, TicketStatus::Resolved] as $status) {
            $ticket = Ticket::factory()->create(['status' => $status, 'assignee_id' => User::factory()->create(['role' => Role::Agent])->id]);
            $this->patch('/tickets/'.$ticket->id.'/assignment', ['assignee_id' => $admin->id])->assertRedirect();
            $this->assertSame($status === TicketStatus::Open ? TicketStatus::Assigned : $status, $ticket->fresh()->status);
            $response = $this->deleteJson('/tickets/'.$ticket->id.'/assignment');
            if (in_array($status, [TicketStatus::Open, TicketStatus::Assigned], true)) {
                $response->assertRedirect();
            } else {
                $response->assertUnprocessable()->assertJsonValidationErrors('assignee_id');
                $this->assertSame(1, $ticket->activities()->count());
            }
        }
        $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);
        $this->patch('/tickets/'.$closed->id.'/assignment', ['assignee_id' => $admin->id])->assertForbidden();
        $this->post('/tickets/'.$closed->id.'/self-assign')->assertForbidden();
        $this->delete('/tickets/'.$closed->id.'/assignment')->assertForbidden();
    }

    public function test_validation_guests_and_foreign_employee(): void
    {
        $ticket = Ticket::factory()->create();
        $employee = User::factory()->create();
        $base = '/tickets/'.$ticket->id;
        $this->patch($base.'/assignment', [])->assertRedirect('/login');
        $this->post($base.'/self-assign')->assertRedirect('/login');
        $this->delete($base.'/assignment')->assertRedirect('/login');
        $this->actingAs($employee)->patch($base.'/assignment', [])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => Role::Agent]));
        foreach ([null, 999999, $employee->id, 'bad'] as $id) {
            $this->patchJson($base.'/assignment', ['assignee_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');
        }
        $this->postJson($base.'/self-assign', ['assignee_id' => 1])->assertUnprocessable();
        $this->deleteJson($base.'/assignment', ['status' => 'OPEN'])->assertUnprocessable();
        $this->assertSame(0, $ticket->activities()->count());
    }
}
