<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionTicketRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && ($this->user()?->can('view', $ticket) ?? false);
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(TicketStatus::class)]];
    }
}
