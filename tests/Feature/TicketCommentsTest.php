<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_comments_all_roles_and_internal_staff_only(): void
    {
        foreach (Role::cases() as $role) {
            $actor = User::factory()->create(['role' => $role]);
            $ticket = Ticket::factory()->create(['requester_id' => $actor->id]);
            $url = '/tickets/'.$ticket->id.'/comments';
            $this->actingAs($actor)->post($url, ['body' => 'Public reply'])->assertRedirect()->assertSessionHas('success');
            $comment = $ticket->comments()->sole();
            $this->assertFalse($comment->is_internal);
            $this->assertSame($actor->id, $comment->author_id);
            $this->assertSame(TicketEvent::Commented, $ticket->activities()->sole()->event);
            $response = $this->postJson($url, ['body' => 'Secret internal note', 'is_internal' => true]);
            if ($role === Role::Employee) {
                $response->assertUnprocessable()->assertJsonValidationErrors('is_internal');
                $this->assertSame(1, $ticket->comments()->count());
            } else {
                $response->assertRedirect();
                $this->assertTrue($ticket->comments()->orderByDesc('id')->first()->is_internal);
                $this->assertSame(2, $ticket->activities()->count());
            }
        }
    }

    public function test_employee_response_never_contains_internal_bodies_and_staff_can_read(): void
    {
        $ticket = Ticket::factory()->create();
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'is_internal' => true, 'body' => 'INTERNAL_SECRET_897']);
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'is_internal' => false, 'body' => 'Public response']);
        $this->actingAs($ticket->requester)->get('/tickets/'.$ticket->id)->assertDontSee('INTERNAL_SECRET_897')->assertInertia(fn (Assert $p) => $p->has('comments.data', 1)->where('comments.data.0.body', 'Public response')->where('can.internal', false));
        $this->get('/tickets')->assertDontSee('INTERNAL_SECRET_897');
        $this->actingAs(User::factory()->create(['role' => Role::Agent]))->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->has('comments.data', 2));
    }

    public function test_validation_closed_ticket_foreign_user_and_guests(): void
    {
        $ticket = Ticket::factory()->create();
        $url = '/tickets/'.$ticket->id.'/comments';
        $this->post($url, [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post($url, ['body' => 'foreign comment'])->assertForbidden();
        $this->actingAs($ticket->requester);
        foreach ([['body' => ''], ['body' => '   '], ['body' => str_repeat('a', 2001)], ['body' => []], ['body' => 'ok', 'is_internal' => 'bad'], ['body' => 'ok', 'author_id' => 999]] as $data) {
            $this->postJson($url, $data)->assertUnprocessable();
        }
        $ticket->update(['status' => TicketStatus::Closed]);
        foreach ([$ticket->requester, User::factory()->create(['role' => Role::Agent]), User::factory()->create(['role' => Role::Admin])] as $actor) {
            $this->actingAs($actor)->post($url, ['body' => 'Cannot comment'])->assertForbidden();
        }
        $this->assertSame(0, $ticket->comments()->count());
    }

    public function test_comments_paginate_and_invalid_page_is_rejected(): void
    {
        $ticket = Ticket::factory()->create();
        TicketComment::factory()->count(25)->create(['ticket_id' => $ticket->id, 'is_internal' => false]);
        $this->actingAs($ticket->requester)->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->has('comments.data', 20)->where('comments.total', 25));
        $this->get('/tickets/'.$ticket->id.'?comments_page=2')->assertInertia(fn (Assert $p) => $p->has('comments.data', 5));
        $this->getJson('/tickets/'.$ticket->id.'?comments_page=-1')->assertUnprocessable();
    }
}
