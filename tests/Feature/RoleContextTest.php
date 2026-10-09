<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_props_expose_only_safe_fields_and_role_capabilities(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get('/settings/profile')->assertInertia(fn (Assert $page) => $page
                ->has('auth.user', 3)
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.role', $role->value)
                ->missing('auth.user.email')
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->missing('auth.user.sessions')
                ->where('auth.can.viewAnyTicket', $role !== Role::Employee)
                ->where('auth.can.manageCategories', $role === Role::Admin)
                ->where('auth.can.managePriorities', $role === Role::Admin)
                ->where('auth.can.manageUsers', $role === Role::Admin)
                ->where('profile.email', $user->email));
        }
    }

    public function test_guests_have_no_privileged_capabilities(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', null)
            ->where('auth.can', ['viewAnyTicket' => false, 'manageCategories' => false, 'managePriorities' => false, 'manageUsers' => false]));
    }

    public function test_local_seeder_is_idempotent_and_does_not_overwrite_existing_accounts(): void
    {
        Storage::fake('local');
        Notification::fake();
        $this->app->instance('env', 'local');
        $this->app->make(DatabaseSeeder::class)->run();
        $this->assertDatabaseCount('users', 8);
        foreach (['employee' => Role::Employee, 'agent' => Role::Agent, 'admin' => Role::Admin] as $name => $role) {
            $this->assertDatabaseHas('users', ['email' => $name.'@example.test', 'role' => $role->value]);
        }
        $employee = User::where('email', 'employee@example.test')->firstOrFail();
        $employee->name = 'Existing name';
        $employee->password = 'changed-password';
        $employee->role = Role::Agent;
        $employee->save();
        $password = $employee->getRawOriginal('password');
        $this->app->make(DatabaseSeeder::class)->run();
        $this->assertDatabaseCount('users', 8);
        $this->assertSame('Existing name', $employee->fresh()->name);
        $this->assertSame($password, $employee->fresh()->getRawOriginal('password'));
        $this->assertSame(Role::Agent, $employee->fresh()->role);
    }

    public function test_seeder_does_not_run_outside_local_environment(): void
    {
        foreach (['testing', 'production', 'staging'] as $environment) {
            $this->app->instance('env', $environment);
            $this->app->make(DatabaseSeeder::class)->run();
            $this->assertDatabaseCount('users', 0);
        }
    }
}
