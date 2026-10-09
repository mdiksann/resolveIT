<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_dashboard_is_a_product_placeholder(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Agent]))->get('/dashboard')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Dashboard')->missing('database')->missing('environment')->where('appName', 'ResolveIT')->where('auth.canAccessAdmin', false));
    }

    public function test_regular_users_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => Role::Admin])->save();
        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/users');
    }

    public function test_profile_updates_validate_normalize_and_ignore_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch('/settings/profile', ['name' => ' Updated ', 'email' => 'UPDATED@example.test', 'role' => 'ADMIN'])->assertSessionHas('success');
        $this->assertSame('Updated', $user->fresh()->name);
        $this->assertSame('updated@example.test', $user->fresh()->email);
        $this->assertSame(Role::Employee, $user->fresh()->role);
        $this->patch('/settings/profile', ['name' => '', 'email' => 'bad'])->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_rejects_duplicate_email(): void
    {
        $other = User::factory()->create();
        $this->actingAs(User::factory()->create())->patch('/settings/profile', ['name' => 'User', 'email' => $other->email])->assertSessionHasErrors('email');
    }

    public function test_json_validation_uses_422(): void
    {
        $this->actingAs(User::factory()->create())->patchJson('/settings/profile', ['name' => '', 'email' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_unknown_page_has_friendly_404(): void
    {
        $this->get('/missing')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    }

    public function test_production_errors_do_not_expose_internal_messages(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/test-error', fn () => throw new \RuntimeException('private-database-detail'));
        $this->get('/test-error')->assertStatus(500)->assertDontSee('private-database-detail')->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 500));
    }
}
