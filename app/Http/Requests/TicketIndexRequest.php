<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketIndexRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('search'))) {
            $this->merge(['search' => trim($this->input('search'))]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', 'integer', Rule::exists('priorities', 'id')],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'assignee' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', [Role::Agent->value, Role::Admin->value])],
            'mine' => ['nullable', 'boolean'], 'overdue' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'due'])],
            'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', Rule::in([20])],
        ];
    }
}
