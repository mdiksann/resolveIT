<?php

namespace App\Http\Controllers;

use App\Enums\TicketEvent;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TicketCommentController extends Controller
{
    public function store(StoreCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        DB::transaction(function () use ($request, $ticket): void {
            $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('comment', $locked);
            $internal = $request->boolean('is_internal');
            if ($internal) {
                Gate::authorize('addInternalNote', $locked);
            }
            $comment = $locked->comments()->create([...$request->validated(), 'is_internal' => $internal, 'author_id' => $request->user()->id]);
            TicketActivity::record($locked, TicketEvent::Commented, $request->user(), ['comment_id' => $comment->id, 'is_internal' => $internal]);
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Comment added.');
    }
}
