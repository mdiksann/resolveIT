<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && ($this->user()?->can('changeRole', $target) ?? false);
    }

    public function rules(): array
    {
        return ['role' => ['required', 'string', Rule::enum(Role::class)]];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_diff(array_keys($this->all()), ['role', '_token', '_method']) as $field) {
                $validator->errors()->add($field, 'Unexpected field.');
            }
        }];
    }
}
