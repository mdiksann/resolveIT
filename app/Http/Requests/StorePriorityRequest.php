<?php

namespace App\Http\Requests;

use App\Models\Priority;
use Illuminate\Foundation\Http\FormRequest;

class StorePriorityRequest extends FormRequest
{
    use NormalizesConfigurationName, RejectsUnknownFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Priority::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => $this->nameRules('priorities'),
            'rank' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'sla_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
