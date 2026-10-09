<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAttachmentRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');
        $attachment = $this->route('attachment');

        return $ticket instanceof Ticket && $attachment instanceof TicketAttachment
            && ($this->user()?->can('deleteAttachment', [$ticket, $attachment]) ?? false);
    }

    public function rules(): array
    {
        return [];
    }
}
