<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_list_is_paginated_safe_and_has_bounded_ticket_counts(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $agent = User::factory()->create(['role' => Role::Agent]);
        User::factory()->count(23)->create();
        Ticket::factory()->count(3)->create(['requester_id' => $admin->id, 'assignee_id' => $agent->id]);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_contains($query->sql, 'from "users"')) {
                $queries[] = $query->sql;
            }
        });
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin')->has('users.data', 20)->where('users.total', 25)
            ->where('users.per_page', 20)->where('users.current_page', 1)
            ->where('users.data.0.requested_tickets_count', 3)
            ->where('users.data.1.assigned_tickets_count', 3)
            ->has('users.data.0', 6)->missing('users.data.0.password')->missing('users.data.0.remember_token')
            ->where('roles.0', ['value' => Role::Employee->value, 'label' => 'Employee'])
            ->where('roles.2', ['value' => Role::Admin->value, 'label' => 'Administrator']));
        $this->assertCount(2, $queries);
        $this->get('/admin/users?page=2')->assertInertia(fn (Assert $page) => $page->has('users.data', 5)->where('users.current_page', 2));
        $this->get('/admin/users?page=99')->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
    }

    #[DataProvider('deniedActors')]
    public function test_user_list_and_role_mutation_are_admin_only(?Role $role): void
    {
        $target = User::factory()->create();
        if ($role !== null) {
            $this->actingAs(User::factory()->create(['role' => $role]));
        }
        $list = $this->get('/admin/users');
        $mutation = $this->patch('/admin/users/'.$target->id.'/role', ['role' => Role::Admin->value]);
        if ($role === null) {
            $list->assertRedirect('/login');
            $mutation->assertRedirect('/login');
        } else {
            $list->assertForbidden();
            $mutation->assertForbidden();
        }
        $this->assertSame(Role::Employee, $target->fresh()->role);
    }

    public static function deniedActors(): array
    {
        return [[null], [Role::Employee], [Role::Agent]];
    }

    public function test_admin_role_changes_apply_to_the_next_request_and_log_without_ticket_audit(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $target = User::factory()->create();
        $this->actingAs($target)->get('/dashboard')->assertRedirect('/tickets');
        Log::shouldReceive('info')->once()->with('User role changed.', [
            'actor_id' => $admin->id, 'user_id' => $target->id,
            'from' => Role::Employee->value, 'to' => Role::Agent->value,
        ]);
        $this->actingAs($admin)->from('/admin/users')->patch('/admin/users/'.$target->id.'/role', ['role' => Role::Agent->value])
            ->assertRedirect('/admin/users')->assertSessionHas('success', 'User role updated.');
        $this->assertSame(Role::Agent, $target->fresh()->role);
        $this->assertDatabaseCount('ticket_activities', 0);
        $this->actingAs($target->fresh())->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', Role::Agent->value)->where('auth.can.viewAnyTicket', true));
    }

    public function test_admin_can_demote_another_admin_and_access_is_revoked_on_next_request(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $other = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->patch('/admin/users/'.$other->id.'/role', ['role' => Role::Employee->value])->assertSessionHasNoErrors();
        $this->assertSame(Role::Employee, $other->fresh()->role);
        $this->assertSame(1, User::where('role', Role::Admin)->count());
        $this->actingAs($other->fresh())->get('/admin/users')->assertForbidden();
        $this->get('/dashboard')->assertRedirect('/tickets');
    }

    public function test_self_demotion_is_validation_error_with_one_or_multiple_admins(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        foreach ([1, 2] as $count) {
            if ($count === 2) {
                User::factory()->create(['role' => Role::Admin]);
            }
            foreach ([Role::Employee, Role::Agent] as $role) {
                $this->actingAs($admin)->patchJson('/admin/users/'.$admin->id.'/role', ['role' => $role->value])
                    ->assertUnprocessable()->assertJsonValidationErrors('role');
                $this->assertSame(Role::Admin, $admin->fresh()->role);
            }
        }
    }

    public function test_last_admin_policy_guard_also_handles_a_stale_actor(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $staleActor = new User;
        $staleActor->id = 999;
        $staleActor->role = Role::Admin;
        $this->expectException(ValidationException::class);
        (new UserPolicy)->changeRole($staleActor, $admin, Role::Employee);
    }

    #[DataProvider('invalidRoles')]
    public function test_invalid_role_input_is_rejected(mixed $role): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $target = User::factory()->create();
        $this->actingAs($admin)->patchJson('/admin/users/'.$target->id.'/role', ['role' => $role])
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame(Role::Employee, $target->fresh()->role);
    }

    public static function invalidRoles(): array
    {
        return [['OWNER'], ['USER'], ['admin'], [null], [['ADMIN']], [123], ['']];
    }

    public function test_unknown_fields_are_rejected_and_no_extra_attributes_are_written(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $target = User::factory()->create();
        $this->actingAs($admin)->patchJson('/admin/users/'.$target->id.'/role', ['role' => Role::Admin->value, 'name' => 'Injected'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->assertSame($target->name, $target->fresh()->name);
        $this->assertSame(Role::Employee, $target->fresh()->role);
    }

    public function test_unchanged_role_is_a_successful_noop_and_missing_user_is_404(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        Log::spy();
        $this->actingAs($admin)->patch('/admin/users/'.$admin->id.'/role', ['role' => Role::Admin->value])
            ->assertSessionHas('success', 'Role is already up to date.');
        Log::shouldNotHaveReceived('info');
        $this->patch('/admin/users/999999/role', ['role' => Role::Agent->value])->assertNotFound();
    }

    public function test_user_list_rejects_invalid_page_and_unknown_query_parameters(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->getJson('/admin/users?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->getJson('/admin/users?page=invalid')->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->getJson('/admin/users?sort=password')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_role_is_not_mass_assignable(): void
    {
        $user = new User(['name' => 'Employee', 'email' => 'safe@example.test', 'password' => 'password', 'role' => Role::Admin]);
        $this->assertFalse($user->isFillable('role'));
        $this->assertSame(Role::Employee, $user->role);
    }
}
