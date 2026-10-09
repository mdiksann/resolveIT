<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\TicketAssignmentRequest;
use App\Http\Requests\TicketIndexRequest;
use App\Http\Requests\TicketShowRequest;
use App\Http\Requests\TransitionTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(TicketIndexRequest $request): Response
    {
        $filters = $request->validated();
        $query = Ticket::with(['requester:id,name', 'assignee:id,name', 'priority:id,name,rank', 'category:id,name']);
        if (! $request->user()->can('viewAny', Ticket::class)) {
            $query->where('requester_id', $request->user()->id);
        }
        foreach (['status' => 'status', 'priority' => 'priority_id', 'category' => 'category_id', 'assignee' => 'assignee_id'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where('title', 'ilike', '%'.$filters['search'].'%');
        }
        if ($request->boolean('mine')) {
            $query->where($request->user()->can('viewAny', Ticket::class) ? 'assignee_id' : 'requester_id', $request->user()->id);
        }
        if ($request->boolean('overdue')) {
            $query->where('due_at', '<', now())->whereNotIn('status', [TicketStatus::Resolved, TicketStatus::Closed]);
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'due' => $query->orderBy('due_at')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('Tickets/Index', [
            'generatedAt' => now()->toISOString(),
            'tickets' => $query->paginate(20)->withQueryString()->through(fn (Ticket $ticket) => $this->ticketData($ticket)),
            'filters' => [...$filters, 'mine' => $request->boolean('mine'), 'overdue' => $request->boolean('overdue')],
            'statuses' => array_map(fn (TicketStatus $status) => ['value' => $status->value, 'label' => $status->label()], TicketStatus::cases()),
            'priorities' => Priority::orderBy('rank')->orderBy('id')->get(['id', 'name', 'rank']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'assignees' => User::whereIn('role', [Role::Agent, Role::Admin])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function ticketData(Ticket $ticket): array
    {
        return [
            ...$ticket->only(['id', 'title', 'status', 'category_id', 'priority_id', 'created_at', 'due_at']),
            'number' => '#'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
            'requester' => $ticket->requester?->only(['id', 'name']),
            'assignee' => $ticket->assignee?->only(['id', 'name']),
            'category' => $ticket->category?->only(['id', 'name']),
            'priority' => $ticket->priority->only(['id', 'name', 'rank']),
            'overdue' => $ticket->due_at->isPast() && ! in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true),
        ];
    }

    public function create(): Response
    {
        Gate::authorize('create', Ticket::class);

        return Inertia::render('Tickets/Create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::where('is_active', true)->orderBy('rank')->orderBy('id')->get(['id', 'name', 'rank', 'is_default']),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = DB::transaction(function () use ($request): Ticket {
            $data = $request->validated();
            $priority = Priority::where('is_active', true)
                ->when($data['priority_id'] ?? null, fn ($q, $id) => $q->whereKey($id), fn ($q) => $q->where('is_default', true))
                ->lockForUpdate()->first();
            if (! $priority) {
                throw ValidationException::withMessages(['priority_id' => 'Choose an active priority. Ask an administrator if none are available.']);
            }
            if (($data['category_id'] ?? null) && ! Category::whereKey($data['category_id'])->where('is_active', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['category_id' => 'Choose an active category.']);
            }
            $createdAt = now()->startOfSecond();
            $ticket = new Ticket([
                ...$data, 'priority_id' => $priority->id,
                'requester_id' => $request->user()->id, 'status' => TicketStatus::Open,
                'due_at' => $createdAt->copy()->addHours($priority->sla_hours),
            ]);
            $ticket->created_at = $createdAt;
            $ticket->save();
            TicketActivity::record($ticket, TicketEvent::Created, $request->user());

            return $ticket;
        });
        Log::info('Ticket created.', ['ticket_id' => $ticket->id, 'actor_id' => $request->user()->id]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created.');
    }

    public function edit(Ticket $ticket): Response
    {
        Gate::authorize('update', $ticket);

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket->only(['id', 'title', 'description', 'category_id', 'priority_id']),
            'categories' => Category::where('is_active', true)->orWhere('id', $ticket->category_id)->orderBy('name')->get(['id', 'name']),
            'priorities' => Priority::where('is_active', true)->orWhere('id', $ticket->priority_id)->orderBy('rank')->orderBy('id')->get(['id', 'name', 'rank']),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        DB::transaction(function () use ($request, $ticket): void {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $locked);
            $data = $request->validated();
            foreach (['priority_id' => TicketEvent::PriorityChanged, 'category_id' => TicketEvent::CategoryChanged] as $field => $event) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $locked->$field) {
                    $model = $field === 'priority_id' ? Priority::class : Category::class;
                    $to = $data[$field] ? $model::whereKey($data[$field])->where('is_active', true)->lockForUpdate()->first() : null;
                    if ($data[$field] && ! $to) {
                        throw ValidationException::withMessages([$field => 'Choose an active option.']);
                    }
                    TicketActivity::record($locked, $event, $request->user(), [
                        'from' => $locked->$field, 'to' => $to?->id,
                        'from_name' => $locked->$field ? $model::find($locked->$field)?->name : null, 'to_name' => $to?->name,
                    ]);
                }
            }
            $locked->fill($data);
            $fields = array_values(array_filter(['title', 'description'], fn ($field) => $locked->isDirty($field)));
            if ($fields) {
                TicketActivity::record($locked, TicketEvent::Updated, $request->user(), ['fields' => $fields]);
            }
            $locked->save();
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated.');
    }

    public function assign(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->assignTo(User::findOrFail($request->validated('assignee_id')), $request->user());
        Log::info('Ticket assigned.', ['ticket_id' => $ticket->id, 'actor_id' => $request->user()->id, 'assignee_id' => $ticket->assignee_id]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket assigned.');
    }

    public function selfAssign(TicketAssignmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->assignTo($request->user(), $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket assigned to you.');
    }

    public function unassign(TicketAssignmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->assignTo(null, $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket unassigned.');
    }

    public function transition(TransitionTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->transitionTo(TicketStatus::from($request->validated('status')), $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket status updated.');
    }

    public function show(TicketShowRequest $request, Ticket $ticket): Response
    {
        // Conceal the existence of foreign tickets from employees.
        abort_unless(Gate::allows('view', $ticket), 404);

        Gate::authorize('viewActivity', $ticket);
        $ticket->load(['requester:id,name', 'assignee:id,name', 'category:id,name', 'priority:id,name,rank']);

        return Inertia::render('Tickets/Show', [
            'generatedAt' => now()->toISOString(),
            'ticket' => [...$this->ticketData($ticket), ...$ticket->only(['description', 'resolved_at', 'closed_at'])],
            'priorities' => Priority::orderBy('rank')->orderBy('id')->get(['id', 'name', 'rank']),
            'assignees' => Gate::allows('assign', $ticket) ? User::whereIn('role', [Role::Agent, Role::Admin])->orderBy('name')->get(['id', 'name']) : [],
            'transitions' => array_values(array_map(fn (TicketStatus $status) => ['value' => $status->value, 'label' => $status->label()], array_filter($ticket->availableTransitions(), fn (TicketStatus $status) => Gate::allows('transitionStatus', [$ticket, $status])))),
            'activities' => $ticket->activities()->when(! Gate::allows('viewInternalNotes', $ticket), fn ($q) => $q->whereRaw("COALESCE((properties->>'is_internal')::boolean, false) = false"))
                ->with('actor:id,name')->orderByDesc('id')->paginate(20, ['*'], 'activities_page')->withQueryString()->through(fn (TicketActivity $activity) => [
                    ...$activity->only(['id', 'created_at']), 'event' => $activity->getRawOriginal('event'),
                    'summary' => $activity->summary(), 'actor' => $activity->actor?->only(['id', 'name']),
                    'relative_time' => $activity->created_at->diffForHumans(),
                ]),
            'attachments' => $ticket->attachments()->when(! Gate::allows('viewInternalNotes', $ticket), fn ($q) => $q->whereDoesntHave('comment', fn ($q) => $q->where('is_internal', true)))
                ->with('uploader:id,name')->orderByDesc('id')->paginate(20, ['*'], 'attachments_page')->withQueryString()->through(fn ($file) => [
                    ...$file->only(['id', 'original_name', 'mime_type', 'size_bytes', 'created_at']),
                    'uploader' => $file->uploader?->only(['id', 'name']), 'can_delete' => Gate::allows('deleteAttachment', [$ticket, $file]),
                    'download_url' => route('tickets.attachments.show', [$ticket, $file]),
                ]),
            'comments' => $ticket->comments()->when(! Gate::allows('viewInternalNotes', $ticket), fn ($q) => $q->where('is_internal', false))
                ->with('author:id,name')->orderByDesc('id')->paginate(20, ['*'], 'comments_page')->withQueryString()->through(fn ($comment) => [
                    ...$comment->only(['id', 'body', 'is_internal', 'created_at']), 'author' => $comment->author?->only(['id', 'name']),
                ]),
            'can' => ['update' => Gate::allows('update', $ticket), 'assign' => Gate::allows('assign', $ticket), 'attach' => Gate::allows('attach', $ticket), 'comment' => Gate::allows('comment', $ticket), 'internal' => Gate::allows('addInternalNote', $ticket)],
        ]);
    }
}
