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
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_is_complete_idempotent_and_visible_through_authorized_pages(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->app->instance('env', 'local');
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->assertSame(2, User::where('role', Role::Agent)->count());
        $this->assertSame(5, User::where('role', Role::Employee)->count());
        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('priorities', 4);
        $this->assertSame(1, Priority::where('is_default', true)->where('is_active', true)->count());
        $this->assertDatabaseCount('tickets', 40);
        foreach (TicketStatus::cases() as $status) {
            $this->assertSame(8, Ticket::where('status', $status)->count());
        }
        $this->assertSame(8, Ticket::whereNull('assignee_id')->count());
        $this->assertDatabaseCount('ticket_comments', 54);
        $this->assertDatabaseCount('ticket_attachments', 5);
        $this->assertSame(40, TicketActivity::where('event', TicketEvent::Created)->count());
        $this->assertSame(54, TicketActivity::where('event', TicketEvent::Commented)->count());
        $this->assertSame(5, TicketActivity::where('event', TicketEvent::AttachmentAdded)->count());
        $activities = TicketActivity::count();
        $files = Storage::disk('local')->allFiles();
        $snapshot = Ticket::orderBy('id')->get()->toArray();
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('users', 8);
        $this->assertDatabaseCount('categories', 6);
        $this->assertDatabaseCount('priorities', 4);
        $this->assertDatabaseCount('tickets', 40);
        $this->assertDatabaseCount('ticket_comments', 54);
        $this->assertDatabaseCount('ticket_attachments', 5);
        $this->assertSame($activities, TicketActivity::count());
        $this->assertSame($files, Storage::disk('local')->allFiles());
        $this->assertSame($snapshot, Ticket::orderBy('id')->get()->toArray());
        foreach (Ticket::all() as $ticket) {
            $this->assertSame($ticket->created_at->copy()->addHours($ticket->priority->sla_hours)->timestamp, $ticket->due_at->timestamp);
            $this->assertSame(in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true), $ticket->resolved_at !== null);
            $this->assertSame($ticket->status === TicketStatus::Closed, $ticket->closed_at !== null);
        }
        $agent = User::where('email', 'agent@example.test')->firstOrFail();
        $overdue = Ticket::where('due_at', '<', now())->whereNotIn('status', [TicketStatus::Resolved, TicketStatus::Closed])->count();
        $this->assertGreaterThan(0, $overdue);
        $this->actingAs($agent)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('metrics.open', 24)->where('metrics.unassigned', 8)->where('metrics.overdue', $overdue)->has('assignments.data'));
        $this->get('/tickets?overdue=1')->assertInertia(fn (Assert $p) => $p->where('tickets.total', $overdue));
        $note = TicketComment::where('is_internal', true)->firstOrFail();
        $this->get('/tickets/'.$note->ticket_id)->assertInertia(fn (Assert $p) => $p->where('comments.data', fn ($comments) => collect($comments)->contains('is_internal', true)));
        $this->actingAs($note->ticket->requester)->get('/tickets/'.$note->ticket_id)->assertInertia(fn (Assert $p) => $p->where('comments.data', fn ($comments) => ! collect($comments)->contains('is_internal', true)));
        foreach (TicketAttachment::all() as $file) {
            Storage::disk('local')->assertExists($file->path);
            $this->actingAs($file->ticket->requester)->get('/tickets/'.$file->ticket_id.'/attachments/'.$file->id)->assertOk()->assertDownload('demo-diagnostic.txt');
        }
    }

    public function test_existing_configuration_and_accounts_are_preserved(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->app->instance('env', 'local');
        $existing = Priority::factory()->create(['name' => 'custom', 'is_default' => true]);
        Category::factory()->create(['name' => 'network', 'is_active' => false]);
        $admin = User::factory()->create(['email' => 'admin@example.test', 'role' => Role::Employee]);
        $this->seed(DemoSeeder::class);
        $this->assertSame(Role::Employee, $admin->fresh()->role);
        $this->assertTrue($existing->fresh()->is_default);
        $this->assertSame(1, Priority::where('is_default', true)->count());
        $this->assertFalse(Category::where('name', 'network')->firstOrFail()->is_active);
        $this->assertDatabaseCount('tickets', 40);
    }

    public function test_demo_seeder_itself_is_guarded_outside_local(): void
    {
        Storage::fake('local');
        foreach (['testing', 'production', 'staging'] as $environment) {
            $this->app->instance('env', $environment);
            $this->app->make(DemoSeeder::class)->run();
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('categories', 0);
            $this->assertDatabaseCount('priorities', 0);
            $this->assertDatabaseCount('tickets', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }
}
