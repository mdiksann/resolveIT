<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class TicketAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_download_safe_props_and_removal_for_each_role(): void
    {
        Storage::fake('local');
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
            $this->actingAs($user)->post('/tickets/'.$ticket->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('my-secret.log', 'Log entry: VPN timeout')])->assertRedirect()->assertSessionHas('success');
            $attachment = $ticket->attachments()->sole();
            $this->assertSame('local', $attachment->disk);
            $this->assertMatchesRegularExpression('#^tickets/'.$ticket->id.'/[A-Za-z0-9]+\.txt$#', $attachment->path);
            $this->assertStringNotContainsString('my-secret', $attachment->path);
            Storage::disk('local')->assertExists($attachment->path);
            $this->assertSame(TicketEvent::AttachmentAdded, $ticket->activities()->sole()->event);
            $base = '/tickets/'.$ticket->id.'/attachments/'.$attachment->id;
            $response = $this->get($base)->assertOk()->assertDownload('my-secret.log')->assertHeader('Content-Type', 'text/plain; charset=utf-8')->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame('Log entry: VPN timeout', $response->streamedContent());
            $this->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->has('attachments.data', 1)->missing('attachments.data.0.path')->missing('attachments.data.0.disk')->where('attachments.data.0.can_delete', true));
            $this->delete($base)->assertRedirect()->assertSessionHas('success');
            Storage::disk('local')->assertMissing($attachment->path);
            $this->assertDatabaseMissing('ticket_attachments', ['id' => $attachment->id]);
            $this->assertSame(2, $ticket->activities()->count());
            $this->assertSame(TicketEvent::AttachmentRemoved, $ticket->activities()->orderByDesc('id')->first()->event);
        }
    }

    public function test_rejected_types_spoofing_sizes_and_unknown_fields(): void
    {
        Storage::fake('local');
        $ticket = Ticket::factory()->create();
        $this->actingAs($ticket->requester);
        $html = UploadedFile::fake()->createWithContent('evil.html', '<html><body>Script content</body></html>');
        $spoof = new UploadedFile($html->getPathname(), 'pretend.pdf', 'application/pdf', null, true);
        foreach ([null, $html, $spoof, UploadedFile::fake()->createWithContent('evil.exe', 'Plain text'), UploadedFile::fake()->create('huge.txt', 10241, 'text/plain')] as $file) {
            $this->postJson('/tickets/'.$ticket->id.'/attachments', ['file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->postJson('/tickets/'.$ticket->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('valid.txt', 'Log entry'), 'disk' => 'public'])->assertUnprocessable()->assertJsonValidationErrors('disk');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_ownership_download_deletion_scoped_binding_closed_and_private_storage(): void
    {
        Storage::fake('local');
        $ticket = Ticket::factory()->create();
        $owner = $ticket->requester;
        $this->actingAs($owner)->post('/tickets/'.$ticket->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('private.txt', 'Private data')]);
        $file = $ticket->attachments()->sole();
        $url = '/tickets/'.$ticket->id.'/attachments/'.$file->id;
        $other = User::factory()->create();
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->delete($url)->assertForbidden();
        $this->post('/tickets/'.$ticket->id.'/attachments', [])->assertForbidden();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $this->actingAs($agent)->get($url)->assertOk();
        $this->delete($url)->assertForbidden();
        $otherTicket = Ticket::factory()->create();
        $this->get('/tickets/'.$otherTicket->id.'/attachments/'.$file->id)->assertNotFound();
        $this->get('/storage/'.$file->path)->assertNotFound();
        $ticket->update(['status' => TicketStatus::Closed]);
        $this->actingAs($owner)->get($url)->assertOk();
        $this->delete($url)->assertForbidden();
        $this->post('/tickets/'.$ticket->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('closed.txt', 'No upload')])->assertForbidden();
        $ticket->update(['status' => TicketStatus::Open]);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->delete($url)->assertRedirect();
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $file->id]);
        auth()->logout();
        $this->get($url)->assertRedirect('/login');
        $this->post('/tickets/'.$ticket->id.'/attachments', [])->assertRedirect('/login');
        $this->delete($url)->assertRedirect('/login');
    }

    public function test_internal_note_files_are_hidden_and_missing_disk_file_returns_404(): void
    {
        Storage::fake('local');
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->create(['ticket_id' => $ticket->id, 'is_internal' => true]);
        $attachment = TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'comment_id' => $comment->id, 'disk' => 'local', 'path' => 'tickets/internal.txt']);
        Storage::disk('local')->put($attachment->path, 'Internal note log');
        $this->actingAs($ticket->requester)->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->has('attachments.data', 0));
        $url = '/tickets/'.$ticket->id.'/attachments/'.$attachment->id;
        $this->get($url)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => Role::Agent]))->get($url)->assertOk();
        Storage::disk('local')->delete($attachment->path);
        $this->get($url)->assertNotFound();
    }

    public function test_failed_database_write_compensates_uploaded_file(): void
    {
        Storage::fake('local');
        $ticket = Ticket::factory()->create();
        $fail = true;
        TicketActivity::creating(function () use (&$fail): void {
            if ($fail) {
                throw new RuntimeException('Simulated audit failure');
            }
        });
        $this->actingAs($ticket->requester)->post('/tickets/'.$ticket->id.'/attachments', ['file' => UploadedFile::fake()->createWithContent('rollback.txt', 'Log entry')])->assertStatus(500);
        $fail = false;
        $this->assertDatabaseCount('ticket_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_file_delete_rolls_back_record_and_activity(): void
    {
        $ticket = Ticket::factory()->create();
        $file = TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'uploader_id' => $ticket->requester_id, 'disk' => 'local']);
        $disk = \Mockery::mock(Filesystem::class);
        $disk->shouldReceive('exists')->once()->with($file->path)->andReturn(true);
        $disk->shouldReceive('get')->once()->with($file->path)->andReturn('Rollback content');
        $disk->shouldReceive('delete')->once()->with($file->path)->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($ticket->requester)->delete('/tickets/'.$ticket->id.'/attachments/'.$file->id)->assertStatus(500);
        $this->assertDatabaseHas('ticket_attachments', ['id' => $file->id]);
        $this->assertSame(0, $ticket->activities()->count());
    }
}
