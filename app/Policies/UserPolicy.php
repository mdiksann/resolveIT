<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->role === Role::Admin;
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->is($user);
    }

    public function changeRole(User $actor, User $user, ?Role $role = null): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }
        if ($role !== null && $role !== Role::Admin && $user->role === Role::Admin) {
            if ($actor->is($user)) {
                throw ValidationException::withMessages(['role' => 'You cannot remove your own administrator role.']);
            }
            if (User::where('role', Role::Admin)->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'The last administrator must keep their role.']);
            }
        }

        return true;
    }
}
