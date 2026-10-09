<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\PriorityPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigurationPolicyTest extends TestCase
{
    #[DataProvider('roles')]
    public function test_configuration_permissions_are_admin_only(Role $role, bool $allowed): void
    {
        $user = new User;
        $user->role = $role;
        foreach ([[new CategoryPolicy, new Category], [new PriorityPolicy, new Priority]] as [$policy, $record]) {
            $this->assertSame($allowed, $policy->viewAny($user));
            $this->assertSame($allowed, $policy->create($user));
            $this->assertSame($allowed, $policy->update($user, $record));
        }
    }

    public static function roles(): array
    {
        return [[Role::Employee, false], [Role::Agent, false], [Role::Admin, true]];
    }
}
