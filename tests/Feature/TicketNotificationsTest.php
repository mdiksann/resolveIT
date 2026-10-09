<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_and_reassign_notify_only_the_new_assignee(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $agent = User::factory()->create(['role' => Role::Agent]);
        $newAgent = User::factory()->create(['role' => Role::Agent]);
        $ticket = Ticket::factory()->create();
        $url = '/tickets/'.$ticket->id;
        $this->actingAs($admin)->patch($url.'/assignment', ['assignee_id' => $agent->id])->assertRedirect();
        Notification::assertSentTo($agent, TicketAssignedNotification::class);
        Notification::assertNotSentTo([$ticket->requester, $admin, $newAgent], TicketAssignedNotification::class);
        Notification::fake();
        $this->patch($url.'/assignment', ['assignee_id' => $newAgent->id])->assertRedirect();
        Notification::assertSentTo($newAgent, TicketAssignedNotification::class);
        Notification::assertNotSentTo([$ticket->requester, $admin, $agent], TicketAssignedNotification::class);
        Notification::assertCount(1);
        Notification::fake();
        $this->delete($url.'/assignment')->assertRedirect();
        Notification::assertNothingSent();
        $this->post($url.'/self-assign')->assertRedirect();
        Notification::assertSentTo($admin, TicketAssignedNotification::class);
        Notification::assertCount(1);
    }

    public function test_only_entry_into_resolved_notifies_requester_including_reresolution(): void
    {
        Notification::fake();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress, 'assignee_id' => $agent->id]);
        $url = '/tickets/'.$ticket->id.'/status';
        $this->actingAs($agent)->patch($url, ['status' => TicketStatus::Resolved->value])->assertRedirect();
        Notification::assertSentTo($ticket->requester, TicketResolvedNotification::class);
        Notification::assertNotSentTo($agent, TicketResolvedNotification::class);
        Notification::assertCount(1);
        Notification::fake();
        $this->actingAs($ticket->requester)->patch($url, ['status' => TicketStatus::InProgress->value])->assertRedirect();
        Notification::assertNothingSent();
        $this->actingAs($agent)->patch($url, ['status' => TicketStatus::Resolved->value])->assertRedirect();
        Notification::assertSentToTimes($ticket->requester, TicketResolvedNotification::class, 1);
        Notification::fake();
        $this->actingAs($ticket->requester)->patch($url, ['status' => TicketStatus::Closed->value])->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_creation_edits_and_comments_send_no_notifications(): void
    {
        Notification::fake();
        $employee = User::factory()->create();
        Priority::factory()->create(['is_default' => true]);
        $this->actingAs($employee)->post('/tickets', ['title' => 'VPN failure', 'description' => 'Unable to connect to VPN'])->assertRedirect();
        $ticket = Ticket::sole();
        $this->patch('/tickets/'.$ticket->id, ['title' => 'VPN still failing'])->assertRedirect();
        $this->post('/tickets/'.$ticket->id.'/comments', ['body' => 'Additional information'])->assertRedirect();
        Notification::assertNothingSent();
    }

    public function test_mail_contracts_are_queued_after_commit_with_links_and_worker_limits(): void
    {
        $ticket = Ticket::factory()->create();
        foreach ([new TicketAssignedNotification($ticket), new TicketResolvedNotification($ticket)] as $notification) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertTrue($notification->afterCommit);
            $this->assertSame(['mail'], $notification->via($ticket->requester));
            $this->assertSame(3, $notification->tries);
            $this->assertLessThan(config('queue.connections.database.retry_after'), $notification->timeout);
            $mail = $notification->toMail($ticket->requester);
            $this->assertStringContainsString($notification->number, $mail->subject);
            $this->assertStringContainsString($ticket->title, implode(' ', $mail->introLines));
            $this->assertSame(route('tickets.show', $ticket), $mail->actionUrl);
            $this->assertStringStartsWith('http', $mail->actionUrl);
        }
    }
}
