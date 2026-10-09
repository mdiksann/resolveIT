<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Priority;
use App\Models\User;

class PriorityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Priority $record): bool
    {
        return $this->viewAny($user);
    }
}
