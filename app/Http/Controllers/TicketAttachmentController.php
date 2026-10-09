<?php

namespace App\Http\Controllers;

use App\Enums\TicketEvent;
use App\Http\Requests\DeleteAttachmentRequest;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Ticket $ticket): RedirectResponse
    {
        $file = $request->file('file');
        $path = null;
        try {
            DB::transaction(function () use ($request, $ticket, $file, &$path): void {
                $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('attach', $locked);
                $path = $file->store('tickets/'.$ticket->id, 'local');
                if (! $path) {
                    throw new RuntimeException('Attachment storage failed.');
                }
                $name = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $file->getClientOriginalName()))), 0, 255);
                $attachment = $locked->attachments()->create([
                    'uploader_id' => $request->user()->id, 'disk' => 'local', 'path' => $path,
                    'original_name' => $name ?: 'attachment', 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(),
                ]);
                TicketActivity::record($locked, TicketEvent::AttachmentAdded, $request->user(), ['attachment_id' => $attachment->id, 'name' => $attachment->original_name]);
            });
        } catch (Throwable $error) {
            if ($path) {
                if (! Storage::disk('local')->delete($path)) {
                    report(new RuntimeException('Could not clean up failed attachment upload.'));
                }
            }
            throw $error;
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Attachment uploaded.');
    }

    public function show(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_if($attachment->comment?->is_internal && ! Gate::allows('viewInternalNotes', $ticket), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store',
        ]);
    }

    public function destroy(DeleteAttachmentRequest $request, Ticket $ticket, TicketAttachment $attachment): RedirectResponse
    {
        $disk = Storage::disk($attachment->disk);
        // The upload ceiling bounds this rollback backup at 10 MB. Database rollback cannot restore files.
        $contents = $disk->exists($attachment->path) ? $disk->get($attachment->path) : null;
        $removed = false;
        try {
            DB::transaction(function () use ($request, $ticket, $attachment, $disk, &$removed): void {
                $locked = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
                $file = $locked->attachments()->whereKey($attachment->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('deleteAttachment', [$locked, $file]);
                $file->delete();
                TicketActivity::record($locked, TicketEvent::AttachmentRemoved, $request->user(), ['attachment_id' => $file->id, 'name' => $file->original_name]);
                if (! $disk->delete($file->path)) {
                    throw new RuntimeException('Attachment removal failed.');
                }
                $removed = true;
            });
        } catch (Throwable $error) {
            if ($removed && $contents !== null && ! $disk->put($attachment->path, $contents)) {
                report(new RuntimeException('Could not restore attachment after database rollback.'));
            }
            throw $error;
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Attachment removed.');
    }
}
