<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryGrowthTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_show_and_dashboard_queries_do_not_grow_per_row(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        $this->actingAs($agent);
        $first = $this->fixture($agent);
        $paths = ['/tickets', '/tickets/'.$first->id, '/dashboard'];
        $counts = [];
        foreach ($paths as $path) {
            $counts[$path] = $this->selectCount($path);
        }
        for ($i = 0; $i < 19; $i++) {
            $this->fixture($agent);
            // Distinct authors/uploaders/actors detect missing eager loads in detail loops.
            $author = User::factory()->create();
            TicketComment::factory()->create(['ticket_id' => $first->id, 'author_id' => $author->id, 'is_internal' => false]);
            TicketAttachment::factory()->create(['ticket_id' => $first->id, 'uploader_id' => $author->id, 'comment_id' => null]);
            TicketActivity::record($first, TicketEvent::Created, $author);
        }
        foreach ($paths as $path) {
            $count = $this->selectCount($path);
            $this->assertSame($counts[$path], $count, $path.' must batch relations regardless of row count.');
            $this->assertLessThanOrEqual($path === '/dashboard' ? 6 : ($path === '/tickets' ? 10 : 17), $count);
        }
    }

    private function fixture(User $agent): Ticket
    {
        $ticket = Ticket::factory()->create(['assignee_id' => $agent->id]);
        TicketComment::factory()->create(['ticket_id' => $ticket->id, 'is_internal' => false]);
        TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'comment_id' => null]);
        TicketActivity::record($ticket, TicketEvent::Created, $agent);

        return $ticket;
    }

    private function selectCount(string $path): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($path)->assertOk();
        $count = count(array_filter(DB::getQueryLog(), fn ($query) => str_starts_with(strtolower($query['query']), 'select')));
        DB::disableQueryLog();
        DB::flushQueryLog();

        return $count;
    }
}
