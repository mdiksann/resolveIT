<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends StoreTicketRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && ($this->user()?->can('update', $ticket) ?? false);
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');
        $rules = parent::rules();
        $rules['title'] = ['sometimes', ...$rules['title']];
        $rules['description'] = ['sometimes', ...$rules['description']];
        // Retired choices may remain unchanged; replacements must be active.
        $rules['category_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('categories', 'id')->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $ticket->category_id))];
        $rules['priority_id'] = ['sometimes', 'required', 'integer', Rule::exists('priorities', 'id')->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $ticket->priority_id))];

        return $rules;
    }
}
