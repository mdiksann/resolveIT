<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_converts_legacy_users_preserves_admins_and_rolls_back(): void
    {
        $migration = require database_path('migrations/2026_10_09_000001_migrate_legacy_user_roles.php');
        $migration->down();
        $employee = User::factory()->create();
        $admin = User::factory()->create(['role' => Role::Admin]);
        DB::table('users')->where('id', $employee->id)->update(['role' => 'USER']);

        $migration->up();

        $this->assertSame(Role::Employee, $employee->fresh()->role);
        $this->assertSame(Role::Admin, $admin->fresh()->role);
        $agent = User::factory()->create(['role' => Role::Agent]);
        $migration->down();
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'role' => 'USER']);
        $this->assertDatabaseHas('users', ['id' => $agent->id, 'role' => 'USER']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => Role::Admin->value]);
        $migration->up();
    }

    public function test_database_default_and_enum_validation_use_employee_roles(): void
    {
        $id = DB::table('users')->insertGetId(['name' => 'Employee', 'email' => 'default@example.test', 'password' => 'unused']);
        $this->assertSame(Role::Employee, User::findOrFail($id)->role);

        foreach (Role::cases() as $role) {
            $this->assertTrue(Validator::make(['role' => $role->value], ['role' => Rule::enum(Role::class)])->passes());
        }
        $this->assertFalse(Validator::make(['role' => 'USER'], ['role' => Rule::enum(Role::class)])->passes());
    }

    public function test_agent_has_no_admin_access_and_shared_props_remain_safe(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);
        $this->actingAs($agent)->get('/admin')->assertForbidden();
        $this->get('/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.role', Role::Agent->value)
            ->where('auth.canAccessAdmin', false)
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'));
    }
}
