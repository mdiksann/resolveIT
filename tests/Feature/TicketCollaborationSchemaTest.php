<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketCollaborationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_factory_and_ticket_relationships(): void
    {
        $comment = TicketComment::factory()->create()->fresh();
        $this->assertInstanceOf(Ticket::class, $comment->ticket);
        $this->assertTrue($comment->author->is($comment->ticket->requester));
        $this->assertFalse($comment->is_internal);
        $this->assertTrue($comment->ticket->comments->first()->is($comment));

        $agent = User::factory()->create(['role' => Role::Agent]);
        $note = TicketComment::factory()->create(['author_id' => $agent->id, 'is_internal' => true]);
        $this->assertTrue($note->fresh()->is_internal);
        $this->assertTrue($note->author->is($agent));
    }

    public function test_attachment_factory_optional_comment_and_relationships(): void
    {
        $attachment = TicketAttachment::factory()->create()->fresh();
        $this->assertNull($attachment->comment);
        $this->assertTrue($attachment->uploader->is($attachment->ticket->requester));
        $this->assertTrue($attachment->ticket->attachments->first()->is($attachment));
        $this->assertSame('local', $attachment->disk);
        $this->assertSame('text/plain', $attachment->mime_type);
        $this->assertIsInt($attachment->size_bytes);
        $this->assertGreaterThan(0, $attachment->size_bytes);
        $this->assertStringStartsWith('tickets/'.$attachment->ticket_id.'/', $attachment->path);
        $this->assertStringNotContainsString($attachment->original_name, $attachment->path);

        $comment = TicketComment::factory()->create();
        $linked = TicketAttachment::factory()->create(['ticket_id' => $comment->ticket_id, 'comment_id' => $comment->id]);
        $this->assertTrue($linked->comment->is($comment));
        $comment->delete();
        $this->assertNull($linked->fresh()->comment_id);
    }

    public function test_activity_factory_uses_jsonb_enum_and_created_at_only(): void
    {
        $activity = TicketActivity::factory()->create(['properties' => ['source' => 'test']])->fresh();
        $this->assertSame(TicketEvent::Created, $activity->event);
        $this->assertSame(['source' => 'test'], $activity->properties);
        $this->assertNotNull($activity->created_at);
        $this->assertFalse(Schema::hasColumn('ticket_activities', 'updated_at'));
        $this->assertSame('jsonb', Schema::getColumnType('ticket_activities', 'properties'));
        $this->assertArrayNotHasKey('updated_at', $activity->getAttributes());
        $this->assertTrue($activity->actor->is($activity->ticket->requester));
        $this->assertTrue($activity->ticket->activities->first()->is($activity));
        $this->assertNull(TicketActivity::factory()->create(['actor_id' => null])->actor);
    }

    #[DataProvider('activityMutations')]
    public function test_activity_model_rejects_mutation(string $operation): void
    {
        $activity = TicketActivity::factory()->create();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Ticket activities are append-only.');
        if ($operation === 'update') {
            $activity->update(['properties' => ['modified' => true]]);
        } else {
            $activity->delete();
        }
    }

    public static function activityMutations(): array
    {
        return [['update'], ['delete']];
    }

    #[DataProvider('childModels')]
    public function test_child_rows_require_valid_ticket_reference(string $model, ?int $ticketId): void
    {
        $user = User::factory()->create();
        $userColumn = match ($model) {
            TicketComment::class => 'author_id',
            TicketAttachment::class => 'uploader_id',
            TicketActivity::class => 'actor_id',
        };

        try {
            $model::factory()->create(['ticket_id' => $ticketId, $userColumn => $user->id]);
            $this->fail('A missing or invalid ticket reference must be rejected.');
        } catch (QueryException $exception) {
            $this->assertSame($ticketId === null ? '23502' : '23503', $exception->errorInfo[0]);
        }
    }

    public static function childModels(): array
    {
        return [
            [TicketComment::class, null], [TicketAttachment::class, null], [TicketActivity::class, null],
            [TicketComment::class, 999999], [TicketAttachment::class, 999999], [TicketActivity::class, 999999],
        ];
    }

    public function test_ticket_children_cascade_at_database_level_and_have_indexes(): void
    {
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->create(['ticket_id' => $ticket->id]);
        TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'comment_id' => $comment->id]);
        TicketActivity::factory()->create(['ticket_id' => $ticket->id]);

        // Verify the schema contract directly; the application exposes no ticket delete route.
        DB::table('tickets')->where('id', $ticket->id)->delete();
        foreach (['ticket_comments', 'ticket_attachments', 'ticket_activities'] as $table) {
            $this->assertDatabaseMissing($table, ['ticket_id' => $ticket->id]);
            $this->assertTrue(Schema::hasIndex($table, ['ticket_id']));
        }
    }

    public function test_deleted_actor_is_cleared_without_removing_activity(): void
    {
        $actor = User::factory()->create(['role' => Role::Agent]);
        $activity = TicketActivity::factory()->create(['actor_id' => $actor->id]);
        $actor->delete();
        $this->assertNull($activity->fresh()->actor_id);
        $this->assertDatabaseHas('ticket_activities', ['id' => $activity->id]);
    }
}
