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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('roles')]
    public function test_all_roles_create_with_server_owned_fields_and_activity(Role $role): void
    {
        $this->freezeSecond();
        $user = User::factory()->create(['role' => $role]);
        $priority = Priority::factory()->create(['is_default' => true, 'sla_hours' => 24]);
        $response = $this->actingAs($user)->post('/tickets', ['title' => ' VPN failure ', 'description' => ' Cannot connect to VPN ']);
        $ticket = Ticket::firstOrFail();
        $response->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('success');
        $this->assertSame($user->id, $ticket->requester_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertNull($ticket->assignee_id);
        $this->assertSame($priority->id, $ticket->priority_id);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours(24)));
        $this->assertSame('VPN failure', $ticket->title);
        $this->assertSame(TicketEvent::Created, $ticket->activities()->sole()->event);
        $this->assertSame($user->id, $ticket->activities()->sole()->actor_id);
    }

    public static function roles(): array
    {
        return array_map(fn ($role) => [$role], Role::cases());
    }

    public function test_chosen_priority_and_category_and_active_create_options(): void
    {
        $this->freezeSecond();
        $priority = Priority::factory()->create(['sla_hours' => 4]);
        $category = Category::factory()->create();
        Priority::factory()->create(['is_active' => false]);
        Category::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->create())->get('/tickets/create')->assertInertia(fn (Assert $p) => $p->component('Tickets/Create')->has('categories', 1)->has('priorities', 1));
        $this->post('/tickets', ['title' => 'Printer fails', 'description' => 'Printer does not respond.', 'priority_id' => $priority->id, 'category_id' => $category->id])->assertSessionHasNoErrors();
        $ticket = Ticket::sole();
        $this->assertSame($category->id, $ticket->category_id);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours(4)));
    }

    #[DataProvider('invalidInputs')]
    public function test_validation_rules(string $field, mixed $value): void
    {
        Priority::factory()->create(['is_default' => true]);
        $this->actingAs(User::factory()->create())->from('/tickets/create')->post('/tickets', array_replace(['title' => 'VPN failure', 'description' => 'Cannot connect to VPN'], [$field => $value]))->assertRedirect('/tickets/create')->assertSessionHasErrors($field);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertDatabaseCount('ticket_activities', 0);
    }

    public static function invalidInputs(): array
    {
        return [['title', null], ['title', 'ab'], ['title', str_repeat('a', 121)], ['title', []], ['description', null], ['description', 'short'], ['description', str_repeat('a', 5001)], ['description', []], ['priority_id', 999], ['priority_id', 'bad'], ['category_id', 999], ['category_id', 'bad'], ['due_at', '2030-01-01'], ['requester_id', 99], ['status', TicketStatus::Closed->value], ['assignee_id', 99]];
    }

    public function test_inactive_choices_and_missing_default_rejected(): void
    {
        $p = Priority::factory()->create(['is_active' => false, 'is_default' => true]);
        $c = Category::factory()->create(['is_active' => false]);
        $this->actingAs(User::factory()->create())->postJson('/tickets', ['title' => 'VPN failure', 'description' => 'Cannot connect to VPN', 'priority_id' => $p->id, 'category_id' => $c->id])->assertUnprocessable()->assertJsonValidationErrors(['priority_id', 'category_id']);
        $this->postJson('/tickets', ['title' => 'VPN failure', 'description' => 'Cannot connect to VPN'])->assertUnprocessable()->assertJsonValidationErrors('priority_id');
    }

    public function test_guests_cannot_create(): void
    {
        $this->get('/tickets/create')->assertRedirect('/login');
        $this->post('/tickets', [])->assertRedirect('/login');
    }
}
