<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SlaContractTest extends TestCase
{
    use RefreshDatabase;

    public static function hours(): array
    {
        return [[1], [4], [24], [72], [720]];
    }

    #[DataProvider('hours')]
    public function test_sla_is_exact_calendar_hours_from_creation_and_never_recalculated(int $hours): void
    {
        // Cross midnight/month/year boundaries; a +/-1 hour mutant must fail.
        $this->travelTo(Carbon::parse('2026-12-31 23:30:00', 'UTC'));
        $user = User::factory()->create(['role' => Role::Admin]);
        $priority = Priority::factory()->create(['sla_hours' => $hours, 'is_default' => true]);
        $this->actingAs($user)->post('/tickets', ['title' => 'Calendar SLA contract', 'description' => 'Verify the calendar-hour deadline.'])->assertSessionHasNoErrors();
        $ticket = Ticket::sole();
        $due = $ticket->due_at->toISOString();
        $this->assertSame($hours * 3600, (int) $ticket->created_at->diffInSeconds($ticket->due_at));
        $priority->update(['sla_hours' => $hours === 720 ? 1 : 720]);
        $replacement = Priority::factory()->create(['sla_hours' => 2]);
        $this->travel(3)->hours();
        $this->patch('/tickets/'.$ticket->id, ['priority_id' => $replacement->id])->assertSessionHasNoErrors();
        $this->assertSame($due, $ticket->fresh()->due_at->toISOString());
    }
}
