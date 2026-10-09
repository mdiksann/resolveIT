<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class TicketShowRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && ($this->user()?->can('view', $ticket) ?? false);
    }

    protected function failedAuthorization(): void
    {
        // Employees receive the same response for missing and foreign tickets.
        abort(404);
    }

    public function rules(): array
    {
        return array_fill_keys(['comments_page', 'attachments_page', 'activities_page'], ['nullable', 'integer', 'min:1']);
    }
}
