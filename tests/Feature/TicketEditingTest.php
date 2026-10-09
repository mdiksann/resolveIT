<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_authorization_matrix_for_roles_ownership_and_states(): void
    {
        foreach (Role::cases() as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $this->actingAs($actor);
            foreach ([true, false] as $own) {
                foreach (TicketStatus::cases() as $status) {
                    $ticket = Ticket::factory()->create(['status' => $status, 'requester_id' => $own ? $actor->id : User::factory()->create()->id]);
                    $allowed = $role === Role::Employee ? $own && in_array($status, [TicketStatus::Open, TicketStatus::Assigned], true) : $status !== TicketStatus::Closed;
                    $get = $this->get('/tickets/'.$ticket->id.'/edit');
                    $update = $this->patch('/tickets/'.$ticket->id, ['title' => 'Corrected title']);
                    if ($allowed) {
                        $get->assertOk()->assertInertia(fn (Assert $p) => $p->component('Tickets/Edit')->where('ticket.title', $ticket->title));
                        $update->assertRedirect()->assertSessionHasNoErrors();
                        $this->assertSame('Corrected title', $ticket->fresh()->title);
                    } else {
                        $get->assertForbidden();
                        $update->assertForbidden();
                        $this->assertSame($ticket->title, $ticket->fresh()->title);
                    }
                    $this->assertTrue($ticket->due_at->equalTo($ticket->fresh()->due_at));
                }
            }
        }
    }

    public function test_changed_priority_category_record_previous_values_and_due_date_stays_fixed(): void
    {
        $ticket = Ticket::factory()->create();
        $newPriority = Priority::factory()->create(['sla_hours' => 300]);
        $newCategory = Category::factory()->create();
        $this->actingAs($ticket->requester)->patch('/tickets/'.$ticket->id, ['priority_id' => $newPriority->id, 'category_id' => $newCategory->id, 'description' => 'Corrected detailed description'])->assertRedirect();
        $events = $ticket->activities()->orderBy('id')->get();
        $this->assertCount(3, $events);
        $this->assertSame(TicketEvent::PriorityChanged, $events[0]->event);
        $this->assertSame($ticket->priority_id, $events[0]->properties['from']);
        $this->assertSame($newPriority->id, $events[0]->properties['to']);
        $this->assertSame(TicketEvent::CategoryChanged, $events[1]->event);
        $this->assertSame($ticket->category_id, $events[1]->properties['from']);
        $this->assertTrue($ticket->due_at->equalTo($ticket->fresh()->due_at));
        $this->patch('/tickets/'.$ticket->id, ['category_id' => null])->assertRedirect();
        $this->assertNull($ticket->fresh()->category_id);
        $this->assertNull($ticket->activities()->orderByDesc('id')->first()->properties['to']);
    }

    public function test_validation_optional_fields_retired_unchanged_and_unknown_fields(): void
    {
        $ticket = Ticket::factory()->create();
        $this->get('/tickets/'.$ticket->id.'/edit')->assertRedirect('/login');
        $this->patch('/tickets/'.$ticket->id, [])->assertRedirect('/login');
        $this->actingAs($ticket->requester);
        foreach (['title' => 'ab', 'description' => 'short', 'priority_id' => null, 'category_id' => 99999, 'due_at' => now()->addYear()->toIso8601String(), 'requester_id' => 99, 'assignee_id' => 99, 'status' => TicketStatus::Closed->value] as $key => $value) {
            $this->patchJson('/tickets/'.$ticket->id, [$key => $value])->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $inactive = Priority::factory()->create(['is_active' => false]);
        $this->patchJson('/tickets/'.$ticket->id, ['priority_id' => $inactive->id])->assertUnprocessable()->assertJsonValidationErrors('priority_id');
        $category = Category::factory()->create(['is_active' => false]);
        $this->patchJson('/tickets/'.$ticket->id, ['category_id' => $category->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        $ticket->priority->update(['is_active' => false]);
        $ticket->category->update(['is_active' => false]);
        $this->patch('/tickets/'.$ticket->id, ['title' => 'Corrected title', 'priority_id' => $ticket->priority_id, 'category_id' => $ticket->category_id])->assertSessionHasNoErrors();
        $this->patch('/tickets/'.$ticket->id, [])->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->activities()->count());
    }
}
