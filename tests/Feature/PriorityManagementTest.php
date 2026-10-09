<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PriorityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_priority_action_role_matrix(): void
    {
        Priority::factory()->create(['is_default' => true]);
        foreach ([null, ...Role::cases()] as $role) {
            auth()->logout();
            if ($role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
            }
            $responses = [$this->get('/admin/priorities'), $this->post('/admin/priorities', ['name' => 'new '.($role?->value ?? 'guest'), 'rank' => 2, 'sla_hours' => 24])];
            foreach (['update', 'deactivate', 'default'] as $action) {
                $p = Priority::factory()->create();
                $responses[] = $this->patch('/admin/priorities/'.$p->id.($action === 'update' ? '' : '/'.$action), $action === 'update' ? ['name' => 'edited '.($role?->value ?? 'guest'), 'sla_hours' => 48] : []);
            }
            foreach ($responses as $index => $response) {
                if (! $role) {
                    $response->assertRedirect('/login');
                } elseif ($role !== Role::Admin) {
                    $response->assertForbidden();
                } else {
                    $response->assertStatus($index === 0 ? 200 : 302)->assertSessionHasNoErrors();
                }
            }
            $this->assertSame(1, Priority::where('is_default', true)->count());
        }
    }

    public function test_first_priority_default_swaps_guarded_retirement_and_request_sequence(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->post('/admin/priorities', ['name' => ' Normal ', 'rank' => 3, 'sla_hours' => 24, 'is_default' => false])->assertSessionHasNoErrors();
        $first = Priority::sole();
        $this->assertSame('normal', $first->name);
        $this->assertTrue($first->is_default);
        $this->post('/admin/priorities', ['name' => 'Urgent', 'rank' => 1, 'sla_hours' => 1, 'is_default' => true])->assertSessionHasNoErrors();
        $second = Priority::where('name', 'urgent')->sole();
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->is_default);
        foreach ([$first, $second, $first] as $priority) {
            $this->patch('/admin/priorities/'.$priority->id.'/default')->assertSessionHasNoErrors();
            $this->assertTrue($priority->fresh()->is_default);
            $this->assertSame(1, Priority::where('is_default', true)->count());
        }
        $this->patchJson('/admin/priorities/'.$first->id, ['is_default' => false])->assertUnprocessable()->assertJsonValidationErrors('is_default');
        $this->patchJson('/admin/priorities/'.$first->id.'/deactivate')->assertUnprocessable()->assertJsonValidationErrors('is_active');
        $this->patch('/admin/priorities/'.$second->id.'/deactivate')->assertSessionHasNoErrors();
        $this->assertFalse($second->fresh()->is_active);
        $this->patchJson('/admin/priorities/'.$second->id.'/default')->assertUnprocessable();
        $this->assertSame(1, Priority::where('is_default', true)->count());
        $this->assertTrue($first->fresh()->is_active);
    }

    public function test_edit_sla_and_retired_ticket_visibility_without_recalculating_due_dates(): void
    {
        $default = Priority::factory()->create(['is_default' => true]);
        $ticket = Ticket::factory()->create();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->patch('/admin/priorities/'.$ticket->priority_id, ['name' => 'Renamed', 'rank' => 4, 'sla_hours' => 720])->assertSessionHasNoErrors();
        $this->assertTrue($ticket->due_at->equalTo($ticket->fresh()->due_at));
        $this->patch('/admin/priorities/'.$ticket->priority_id.'/deactivate')->assertSessionHasNoErrors();
        $this->get('/tickets/create')->assertInertia(fn (Assert $p) => $p->has('priorities', 1)->where('priorities.0.id', $default->id));
        $this->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->where('ticket.priority.name', 'renamed'));
        $this->postJson('/tickets', ['title' => 'Retired priority', 'description' => 'Cannot choose retired priority', 'priority_id' => $ticket->priority_id])->assertUnprocessable()->assertJsonValidationErrors('priority_id');
    }

    public function test_input_bounds_uniqueness_and_unknown_fields(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $priority = Priority::factory()->create(['name' => 'LegacyMixed', 'is_default' => true]);
        $valid = ['name' => 'valid', 'rank' => 2, 'sla_hours' => 24];
        foreach ([['name', 'x'], ['name', str_repeat('a', 61)], ['name', []], ['name', 'legacymixed'], ['rank', 0], ['rank', 1.5], ['rank', 2147483648], ['sla_hours', 0], ['sla_hours', 721], ['sla_hours', 'bad'], ['is_default', 'yes'], ['is_active', true]] as [$field, $value]) {
            $this->postJson('/admin/priorities', array_replace($valid, [$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->postJson('/admin/priorities', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'rank', 'sla_hours']);
        foreach ([1, 720] as $hours) {
            $this->patch('/admin/priorities/'.$priority->id, ['sla_hours' => $hours])->assertSessionHasNoErrors();
            $this->assertSame($hours, $priority->fresh()->sla_hours);
        }
        $this->patchJson('/admin/priorities/'.$priority->id.'/default', ['is_default' => true])->assertUnprocessable();
        $this->patchJson('/admin/priorities/'.$priority->id, ['is_active' => false])->assertUnprocessable();
        $this->assertSame(1, Priority::where('is_default', true)->count());
    }

    public function test_priority_pagination_and_invalid_query(): void
    {
        Priority::factory()->count(23)->create();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/priorities')->assertInertia(fn (Assert $p) => $p->component('Admin/Priorities')->has('priorities.data', 20)->where('priorities.total', 23));
        $this->get('/admin/priorities?page=2')->assertInertia(fn (Assert $p) => $p->has('priorities.data', 3));
        $this->getJson('/admin/priorities?page=0')->assertUnprocessable();
        $this->getJson('/admin/priorities?sort=bad')->assertUnprocessable();
    }
}
