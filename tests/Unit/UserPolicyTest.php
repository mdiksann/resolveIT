<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    #[DataProvider('roles')]
    public function test_role_management_permissions(Role $role, bool $allowed): void
    {
        $actor = new User;
        $actor->id = 1;
        $actor->role = $role;
        $other = new User;
        $other->id = 2;
        $other->role = Role::Employee;
        $policy = new UserPolicy;
        $this->assertSame($allowed, $policy->viewAny($actor));
        foreach (Role::cases() as $target) {
            $this->assertSame($allowed, $policy->changeRole($actor, $other, $target));
        }
        $this->assertTrue($policy->update($actor, $actor));
        $this->assertFalse($policy->update($actor, $other));
    }

    public static function roles(): array
    {
        return [[Role::Employee, false], [Role::Agent, false], [Role::Admin, true]];
    }

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
