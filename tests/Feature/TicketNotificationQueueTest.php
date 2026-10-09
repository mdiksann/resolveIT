<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class TicketNotificationQueueTest extends TestCase
{
    use DatabaseMigrations;

    public function test_dispatch_waits_for_commit_rollbacks_discard_mail_and_worker_failures_are_persisted(): void
    {
        config(['queue.default' => 'database']);
        $agent = User::factory()->create(['role' => Role::Agent]);
        $ticket = Ticket::factory()->create();
        $url = '/tickets/'.$ticket->id.'/assignment';
        $this->actingAs($agent);
        DB::beginTransaction();
        $this->patch($url, ['assignee_id' => $agent->id])->assertRedirect();
        $this->assertDatabaseCount('jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertNull($ticket->fresh()->assignee_id);
        DB::beginTransaction();
        $this->patch($url, ['assignee_id' => $agent->id])->assertRedirect();
        $this->assertDatabaseCount('jobs', 0);
        DB::commit();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
        Event::listen(MessageSending::class, function (): never {
            throw new RuntimeException('Simulated mail transport failure.');
        });
        // Put this job on its final allowed attempt without waiting through retry backoffs.
        DB::table('jobs')->update(['attempts' => 2, 'available_at' => now()->timestamp]);
        Artisan::call('queue:work', ['--once' => true, '--sleep' => 0]);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertStringContainsString('Simulated mail transport failure.', DB::table('failed_jobs')->value('exception'));
        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }
}
