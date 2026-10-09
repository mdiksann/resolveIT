<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_metrics_match_a_fixture_and_queries_are_bounded(): void
    {
        $this->freezeSecond();
        $agent = User::factory()->create(['role' => Role::Agent]);
        $other = User::factory()->create(['role' => Role::Agent]);
        $normal = Priority::factory()->create(['rank' => 2]);
        $urgent = Priority::factory()->create(['rank' => 1]);
        $unused = Priority::factory()->create(['rank' => 3]);
        foreach ([
            [TicketStatus::Open, $normal, null, now()->subHour(), null],
            [TicketStatus::Assigned, $normal, $agent, now()->addDay(), null],
            [TicketStatus::InProgress, $urgent, $agent, now()->subHour(), null],
            [TicketStatus::InProgress, $urgent, $other, now()->addDay(), null],
            [TicketStatus::Resolved, $normal, null, now()->subDay(), now()->subDays(2)],
            [TicketStatus::Closed, $urgent, null, now()->subDay(), now()->subDay()],
            [TicketStatus::Resolved, $urgent, null, now()->subDay(), now()->subDays(8)],
            [TicketStatus::Resolved, $urgent, null, now()->subDay(), now()->addDay()],
            [TicketStatus::Open, $normal, null, now(), null],
            [TicketStatus::Closed, $urgent, null, now()->subDay(), null],
        ] as [$status, $priority, $assignee, $due, $resolved]) {
            Ticket::factory()->create(['status' => $status, 'priority_id' => $priority->id, 'assignee_id' => $assignee?->id, 'due_at' => $due, 'resolved_at' => $resolved]);
        }
        $queries = 0;
        DB::listen(function ($query) use (&$queries): void {
            if (str_starts_with($query->sql, 'select')) {
                $queries++;
            }
        });
        $this->actingAs($agent)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->component('Dashboard')
            ->where('metrics', ['open' => 5, 'unassigned' => 2, 'overdue' => 2, 'resolved_last_7_days' => 2])
            ->where('byStatus.0.total', 2)->where('byStatus.1.total', 1)->where('byStatus.2.total', 2)->where('byStatus.3.total', 3)->where('byStatus.4.total', 2)
            ->where('byPriority.0.id', $urgent->id)->where('byPriority.0.total', 6)->where('byPriority.1.total', 4)->where('byPriority.2.id', $unused->id)->where('byPriority.2.total', 0)
            ->has('assignments.data', 2)->where('assignments.data.0.overdue', true)->where('assignments.data.0.status', TicketStatus::InProgress->value)
            ->missing('assignments.data.0.description')->has('generatedAt'));
        $this->assertLessThanOrEqual(6, $queries);
    }

    public function test_guest_redirect_employee_redirect_and_staff_empty_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $employee = User::factory()->create();
        $this->actingAs($employee)->get('/dashboard')->assertRedirect('/tickets');
        foreach ([Role::Agent, Role::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('metrics', ['open' => 0, 'unassigned' => 0, 'overdue' => 0, 'resolved_last_7_days' => 0])->has('byStatus', 5)->has('byPriority', 0)->has('assignments.data', 0));
        }
    }

    public function test_open_assignments_paginate_and_reject_invalid_input(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        Ticket::factory()->count(22)->create(['assignee_id' => $agent->id]);
        Ticket::factory()->create(['status' => TicketStatus::Closed, 'assignee_id' => $agent->id]);
        $this->actingAs($agent)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('assignments.data', 20)->where('assignments.total', 22));
        $this->get('/dashboard?assignments_page=2')->assertInertia(fn (Assert $p) => $p->has('assignments.data', 2));
        $this->getJson('/dashboard?assignments_page=0')->assertUnprocessable();
        $this->getJson('/dashboard?scope=all')->assertUnprocessable();
    }
}
