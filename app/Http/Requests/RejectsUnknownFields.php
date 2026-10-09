<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

trait RejectsUnknownFields
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), [...array_keys($this->rules()), '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'Unexpected field.');
            }
        }];
    }
}
