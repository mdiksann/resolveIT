<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\DashboardRequest;
use App\Models\Priority;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request): Response|RedirectResponse
    {
        if (! Gate::allows('manage-tickets')) {
            return redirect()->route('tickets.index');
        }
        Gate::authorize('viewAny', Ticket::class);
        $now = now();
        $terminal = [TicketStatus::Resolved->value, TicketStatus::Closed->value];
        // Open work includes Open, Assigned and In Progress. Recent resolutions include later closures.
        $totals = DB::table('tickets')->selectRaw(
            'COUNT(*) FILTER (WHERE status NOT IN (?, ?)) AS open,
             COUNT(*) FILTER (WHERE status NOT IN (?, ?) AND assignee_id IS NULL) AS unassigned,
             COUNT(*) FILTER (WHERE status NOT IN (?, ?) AND due_at < ?) AS overdue,
             COUNT(*) FILTER (WHERE status IN (?, ?) AND resolved_at BETWEEN ? AND ?) AS resolved_last_7_days',
            [...$terminal, ...$terminal, ...$terminal, $now, ...$terminal, $now->copy()->subDays(7), $now]
        )->first();
        $byStatus = DB::table('tickets')->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $byPriority = Priority::leftJoin('tickets', 'tickets.priority_id', '=', 'priorities.id')
            ->select('priorities.id', 'priorities.name', 'priorities.rank')->selectRaw('COUNT(tickets.id)::int AS total')
            ->groupBy('priorities.id')->orderBy('priorities.rank')->orderBy('priorities.id')->get();
        $assignments = Ticket::where('assignee_id', $request->user()->id)->whereNotIn('status', $terminal)
            ->with('priority:id,name,rank')->orderBy('due_at')->orderBy('id')->paginate(20, ['*'], 'assignments_page')->withQueryString()
            ->through(fn (Ticket $ticket) => [
                ...$ticket->only(['id', 'title', 'status', 'due_at']),
                'number' => '#'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
                'priority' => $ticket->priority->only(['id', 'name', 'rank']), 'overdue' => $ticket->due_at->lt($now),
            ]);

        return Inertia::render('Dashboard', [
            'metrics' => array_map(fn ($count) => (int) $count, (array) $totals),
            'byStatus' => array_map(fn (TicketStatus $status) => ['value' => $status->value, 'label' => $status->label(), 'total' => (int) ($byStatus[$status->value] ?? 0)], TicketStatus::cases()),
            'byPriority' => $byPriority, 'assignments' => $assignments, 'generatedAt' => $now->toISOString(),
        ]);
    }
}
