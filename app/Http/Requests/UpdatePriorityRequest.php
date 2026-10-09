<?php

namespace App\Http\Requests;

use App\Models\Priority;

class UpdatePriorityRequest extends StorePriorityRequest
{
    public function authorize(): bool
    {
        $priority = $this->route('priority');

        return $priority instanceof Priority && ($this->user()?->can('update', $priority) ?? false);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = $this->nameRules('priorities', $this->route('priority')?->id);

        return array_map(fn ($rules) => ['sometimes', ...$rules], $rules);
    }
}
