<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigurationIndexRequest extends FormRequest
{
    use RejectsUnknownFields;

    public function authorize(): bool
    {
        return $this->user()?->can('administer') ?? false;
    }

    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1']];
    }
}
