<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && ($this->user()?->can('comment', $ticket) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
    }

    public function rules(): array
    {
        $internal = ['sometimes', 'boolean'];
        if (! $this->user()?->can('addInternalNote', $this->route('ticket'))) {
            $internal[] = Rule::in([false, 0, '0']);
        }

        return ['body' => ['required', 'string', 'min:1', 'max:2000'], 'is_internal' => $internal];
    }
}
