<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreAttachmentRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket && ($this->user()?->can('attach', $ticket) ?? false);
    }

    public function rules(): array
    {
        $types = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'txt', 'log', 'csv', 'zip'];

        return ['file' => ['required', File::types($types)->max('10mb'), 'extensions:'.implode(',', $types)]];
    }
}
