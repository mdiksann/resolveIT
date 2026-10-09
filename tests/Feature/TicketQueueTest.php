<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_scope_cannot_be_overridden_by_filters(): void
    {
        $employee = User::factory()->create();
        $own = Ticket::factory()->create(['requester_id' => $employee->id]);
        $foreign = Ticket::factory()->create(['title' => 'Foreign secret']);
        $this->actingAs($employee)->get('/tickets')->assertInertia(fn (Assert $p) => $p->component('Tickets/Index')->has('tickets.data', 1)->where('tickets.data.0.id', $own->id));
        $this->get('/tickets?search=Foreign&mine=0')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 0));
        $this->get('/tickets?priority='.$foreign->priority_id)->assertInertia(fn (Assert $p) => $p->has('tickets.data', 0));
        $this->get('/tickets')->assertDontSee('Foreign secret');
    }

    public function test_filters_combine_and_sorts_are_stable(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        $category = Category::factory()->create();
        $priority = Priority::factory()->create();
        $match = Ticket::factory()->create(['title' => 'VPN broken', 'status' => TicketStatus::Assigned, 'assignee_id' => $agent->id, 'category_id' => $category->id, 'priority_id' => $priority->id, 'due_at' => now()->subHour(), 'created_at' => now()->subDay()]);
        $newer = Ticket::factory()->create(['title' => 'Other request', 'created_at' => now(), 'due_at' => now()->addDay()]);
        $this->actingAs($agent);
        foreach (['search=VPN', 'status=ASSIGNED', 'priority='.$priority->id, 'category='.$category->id, 'assignee='.$agent->id, 'mine=1', 'overdue=1', 'search=VPN&status=ASSIGNED&priority='.$priority->id.'&category='.$category->id.'&assignee='.$agent->id.'&mine=1&overdue=1'] as $query) {
            $this->get('/tickets?'.$query)->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)->where('tickets.data.0.id', $match->id));
        }
        $this->get('/tickets?search=VPN&status=OPEN')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 0));
        $this->get('/tickets')->assertInertia(fn (Assert $p) => $p->where('tickets.data.0.id', $newer->id));
        foreach (['oldest', 'due'] as $sort) {
            $this->get('/tickets?sort='.$sort)->assertInertia(fn (Assert $p) => $p->where('tickets.data.0.id', $match->id));
        }
    }

    public function test_overdue_excludes_resolved_closed_and_equal_due_time(): void
    {
        $this->freezeSecond();
        Ticket::factory()->create(['due_at' => now()->subSecond()]);
        foreach ([TicketStatus::Resolved, TicketStatus::Closed] as $status) {
            Ticket::factory()->create(['status' => $status, 'due_at' => now()->subDay()]);
        }
        Ticket::factory()->create(['due_at' => now()]);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/tickets?overdue=1')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 1)->where('tickets.data.0.overdue', true));
    }

    public function test_pagination_safe_props_and_query_bound(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        Ticket::factory()->count(25)->create(['assignee_id' => $agent->id]);
        $count = 0;
        DB::listen(function ($query) use (&$count): void {
            if (str_starts_with($query->sql, 'select')) {
                $count++;
            }
        });
        $this->actingAs($agent)->get('/tickets')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 20)->where('tickets.total', 25)->where('tickets.per_page', 20)->where('tickets.current_page', 1)->missing('tickets.data.0.requester.email')->missing('tickets.data.0.description'));
        $this->assertLessThanOrEqual(10, $count);
        $this->get('/tickets?page=2')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 5));
        $this->get('/tickets?page=99')->assertInertia(fn (Assert $p) => $p->has('tickets.data', 0));
    }

    public function test_zero_search_and_false_query_booleans_are_preserved(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        $match = Ticket::factory()->create(['title' => 'Error 0']);
        Ticket::factory()->create(['title' => 'Other error']);
        $this->actingAs($agent)->get('/tickets?search=0&mine=0&overdue=0')->assertInertia(fn (Assert $p) => $p
            ->has('tickets.data', 1)->where('tickets.data.0.id', $match->id)
            ->where('filters.mine', false)->where('filters.overdue', false));
    }

    public function test_invalid_filters_and_guest(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach (['search' => str_repeat('a', 101), 'status' => 'BAD', 'priority' => 99999, 'category' => 99999, 'assignee' => 99999, 'mine' => 'true', 'overdue' => 2, 'sort' => 'password', 'page' => 0, 'per_page' => 100, 'requester_id' => 1] as $key => $value) {
            $this->getJson('/tickets?'.http_build_query([$key => $value]))->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $employee = User::factory()->create();
        $this->getJson('/tickets?assignee='.$employee->id)->assertUnprocessable()->assertJsonValidationErrors('assignee');
    }
}
