<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_users_only_edit_their_own_profile_even_when_admin(): void
    {
        $actor = new User;
        $actor->id = 1;
        $actor->role = Role::Admin;
        $other = new User;
        $other->id = 2;
        $policy = new UserPolicy;
        $this->assertTrue($policy->update($actor, $actor));
        $this->assertFalse($policy->update($actor, $other));
    }
}
