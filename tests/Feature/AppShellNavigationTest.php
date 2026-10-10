<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_props_omit_privileged_navigation_capabilities(): void
    {
        $employee = User::factory()->create(['role' => Role::Employee]);

        $this->actingAs($employee)->get('/tickets')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', Role::Employee->value)
            ->where('auth.can.viewAnyTicket', false)
            ->where('auth.can.manageCategories', false)
            ->where('auth.can.managePriorities', false)
            ->where('auth.can.manageUsers', false));
    }

    public function test_agent_props_enable_queue_and_dashboard_but_omit_admin_capabilities(): void
    {
        $agent = User::factory()->create(['role' => Role::Agent]);

        $this->actingAs($agent)->get('/tickets')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', Role::Agent->value)
            ->where('auth.can.viewAnyTicket', true)
            ->where('auth.can.manageCategories', false)
            ->where('auth.can.managePriorities', false)
            ->where('auth.can.manageUsers', false));
    }

    public function test_admin_props_enable_all_navigation_capabilities(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get('/tickets')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', Role::Admin->value)
            ->where('auth.can.viewAnyTicket', true)
            ->where('auth.can.manageCategories', true)
            ->where('auth.can.managePriorities', true)
            ->where('auth.can.manageUsers', true));
    }

    public function test_direct_urls_are_server_authorized_regardless_of_frontend_links(): void
    {
        $employee = User::factory()->create(['role' => Role::Employee]);

        // Employee cannot access dashboard directly (redirected to own tickets / profile)
        $this->actingAs($employee)->get('/dashboard')->assertRedirect();

        // Employee cannot access admin configuration routes (403 forbidden)
        $this->actingAs($employee)->get('/admin/categories')->assertForbidden();
        $this->actingAs($employee)->get('/admin/priorities')->assertForbidden();
        $this->actingAs($employee)->get('/admin/users')->assertForbidden();
    }

    public function test_logout_requires_post_request(): void
    {
        $user = User::factory()->create();

        // GET on logout does not log out
        $this->actingAs($user)->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);

        // POST logs out and invalidates session
        $this->actingAs($user)->post('/logout')->assertRedirect();
        $this->assertGuest();
    }
}
