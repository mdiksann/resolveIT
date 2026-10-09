<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_lifecycle_records_ordered_events_once_and_conceals_notes(): void
    {
        Storage::fake('local');
        $employee = User::factory()->create();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $priority = Priority::factory()->create(['is_default' => true]);
        $newPriority = Priority::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs($employee)->post('/tickets', ['title' => 'VPN outage', 'description' => 'Cannot connect to the company VPN'])->assertRedirect();
        $ticket = Ticket::sole();
        $url = '/tickets/'.$ticket->id;
        $this->patch($url, ['title' => 'VPN outage corrected'])->assertRedirect();
        $this->patch($url, ['priority_id' => $newPriority->id])->assertRedirect();
        $this->patch($url, ['category_id' => $category->id])->assertRedirect();
        $this->actingAs($agent)->post($url.'/self-assign')->assertRedirect();
        $this->delete($url.'/assignment')->assertRedirect();
        $this->patch($url.'/assignment', ['assignee_id' => $agent->id])->assertRedirect();
        $this->patch($url.'/status', ['status' => TicketStatus::InProgress->value])->assertRedirect();
        $this->post($url.'/comments', ['body' => 'Working on this'])->assertRedirect();
        $this->post($url.'/comments', ['body' => 'INTERNAL_SECRET_LIFECYCLE', 'is_internal' => true])->assertRedirect();
        $this->post($url.'/attachments', ['file' => UploadedFile::fake()->createWithContent('vpn.log', 'VPN failed')])->assertRedirect();
        $file = $ticket->attachments()->sole();
        $this->delete($url.'/attachments/'.$file->id)->assertRedirect();
        $this->patch($url.'/status', ['status' => TicketStatus::Resolved->value])->assertRedirect();
        $this->actingAs($employee)->patch($url.'/status', ['status' => TicketStatus::InProgress->value])->assertRedirect();
        $this->actingAs($agent)->patch($url.'/status', ['status' => TicketStatus::Resolved->value])->assertRedirect();
        $this->actingAs($employee)->patch($url.'/status', ['status' => TicketStatus::Closed->value])->assertRedirect();
        $expected = [TicketEvent::Created, TicketEvent::Updated, TicketEvent::PriorityChanged, TicketEvent::CategoryChanged, TicketEvent::Assigned, TicketEvent::Unassigned, TicketEvent::Assigned, TicketEvent::StatusChanged, TicketEvent::Commented, TicketEvent::Commented, TicketEvent::AttachmentAdded, TicketEvent::AttachmentRemoved, TicketEvent::StatusChanged, TicketEvent::StatusChanged, TicketEvent::StatusChanged, TicketEvent::StatusChanged];
        $this->assertSame($expected, $ticket->activities()->orderBy('id')->get()->pluck('event')->all());
        $this->assertTrue($ticket->fresh()->due_at->equalTo($ticket->created_at->addHours($priority->sla_hours)));
        $this->get($url)->assertDontSee('INTERNAL_SECRET_LIFECYCLE')->assertDontSee('Added internal note')->assertInertia(fn (Assert $p) => $p->has('activities.data', 15)->has('comments.data', 1)->where('ticket.status', TicketStatus::Closed->value));
        $this->actingAs($agent)->get($url)->assertInertia(fn (Assert $p) => $p->has('activities.data', 16)->has('comments.data', 2));
    }

    public function test_bounded_detail_queries_pagination_unknown_event_and_noop_edit(): void
    {
        $ticket = Ticket::factory()->create();
        $agent = User::factory()->create(['role' => Role::Agent]);
        for ($i = 0; $i < 25; $i++) {
            TicketActivity::record($ticket, TicketEvent::Created, $agent);
        }
        TicketComment::factory()->count(25)->create(['ticket_id' => $ticket->id, 'is_internal' => false]);
        TicketAttachment::factory()->count(25)->create(['ticket_id' => $ticket->id, 'comment_id' => null]);
        DB::table('ticket_activities')->insert(['ticket_id' => $ticket->id, 'actor_id' => null, 'event' => 'future_event', 'properties' => '{}', 'created_at' => now()]);
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_starts_with($query->sql, 'select')) {
                $queries++;
            }
        });
        $this->actingAs($agent)->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->has('activities.data', 20)->where('activities.total', 26)->where('activities.data.0.summary', 'Ticket activity')->where('activities.data.0.actor', null));
        $this->assertLessThanOrEqual(17, $queries);
        $this->get('/tickets/'.$ticket->id.'?activities_page=2')->assertInertia(fn (Assert $p) => $p->has('activities.data', 6));
        $this->patch('/tickets/'.$ticket->id, ['title' => $ticket->title])->assertSessionHasNoErrors();
        $this->assertSame(26, $ticket->activities()->count());
        $this->patch('/tickets/'.$ticket->id.'/activities/1')->assertNotFound();
        $this->delete('/tickets/'.$ticket->id.'/activities/1')->assertNotFound();
    }
}
