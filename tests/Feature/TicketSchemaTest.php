<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_resolves_required_and_optional_relations_and_casts(): void
    {
        $this->travelTo(now()->startOfSecond());
        $requester = User::factory()->create();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $category = Category::factory()->create();
        $priority = Priority::factory()->create(['sla_hours' => 24]);
        $ticket = Ticket::factory()->create([
            'requester_id' => $requester->id,
            'assignee_id' => $agent->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status' => TicketStatus::Assigned,
        ])->fresh();

        $this->assertTrue($ticket->requester->is($requester));
        $this->assertTrue($ticket->assignee->is($agent));
        $this->assertTrue($ticket->category->is($category));
        $this->assertTrue($ticket->priority->is($priority));
        $this->assertSame(TicketStatus::Assigned, $ticket->status);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours(24)));
        $this->assertNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);

        $unassigned = Ticket::factory()->create(['category_id' => null])->fresh();
        $this->assertNull($unassigned->assignee);
        $this->assertNull($unassigned->category);
        $this->assertInstanceOf(User::class, $unassigned->requester);
        $this->assertInstanceOf(Priority::class, $unassigned->priority);
    }

    public function test_database_defaults_status_to_open(): void
    {
        $attributes = Ticket::factory()->raw();
        unset($attributes['status']);
        $id = DB::table('tickets')->insertGetId($attributes);
        $this->assertSame(TicketStatus::Open, Ticket::findOrFail($id)->status);
    }

    public function test_due_date_is_required_by_database(): void
    {
        $this->expectException(QueryException::class);
        Ticket::factory()->create(['due_at' => null]);
    }

    public function test_database_rejects_missing_requester_reference(): void
    {
        $this->expectException(QueryException::class);
        Ticket::factory()->create(['requester_id' => 999999]);
    }

    public function test_requester_cannot_be_deleted_while_tickets_reference_them(): void
    {
        $ticket = Ticket::factory()->create();
        $this->expectException(QueryException::class);
        $ticket->requester->delete();
    }

    public function test_optional_references_are_cleared_when_records_are_deleted(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        $ticket = Ticket::factory()->create(['assignee_id' => $agent->id]);
        $agent->delete();
        $ticket->category->delete();

        $this->assertNull($ticket->fresh()->assignee_id);
        $this->assertNull($ticket->fresh()->category_id);
    }

    public function test_priority_cannot_be_deleted_while_referenced(): void
    {
        $ticket = Ticket::factory()->create();
        $this->expectException(QueryException::class);
        $ticket->priority->delete();
    }

    public function test_queue_filter_columns_have_indexes(): void
    {
        foreach (['status', 'requester_id', 'assignee_id', 'due_at', 'category_id', 'priority_id'] as $column) {
            $this->assertTrue(Schema::hasIndex('tickets', [$column]), $column.' should be indexed');
        }
    }
}
