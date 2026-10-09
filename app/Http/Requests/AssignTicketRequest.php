<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Validation\Rule;

class AssignTicketRequest extends TicketAssignmentRequest
{
    public function rules(): array
    {
        return ['assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('role', [Role::Agent->value, Role::Admin->value])]];
    }
}
