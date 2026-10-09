<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_registration_normalizes_input_and_cannot_assign_admin(): void
    {
        $this->post('/register', ['name' => '  New User  ', 'email' => 'NEW@example.test', 'password' => 'long-test-password', 'password_confirmation' => 'long-test-password', 'role' => 'ADMIN'])->assertRedirect('/');
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('New User', $user->name);
        $this->assertSame(Role::Employee, $user->role);
        $this->assertTrue(Hash::check('long-test-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_authentication_views_render(): void
    {
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example-token?email=user@example.test')->assertOk();
        $this->actingAs(User::factory()->create())->get('/user/confirm-password')->assertOk();
    }

    public function test_registration_rejects_array_input(): void
    {
        $this->post('/register', ['name' => ['unexpected'], 'email' => ['unexpected'], 'password' => 'long-test-password', 'password_confirmation' => 'long-test-password'])->assertSessionHasErrors(['name', 'email']);
        $this->assertGuest();
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $this->post('/register', ['email' => 'invalid', 'password' => 'short'])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }

    public function test_authentication_rejects_array_emails_without_server_errors(): void
    {
        foreach (['/login', '/forgot-password', '/reset-password'] as $url) {
            $this->post($url, ['email' => ['unexpected'], 'password' => 'long-test-password'])
                ->assertSessionHasErrors('email');
        }
    }

    public function test_login_regenerates_session_and_logout_invalidates_it(): void
    {
        $user = User::factory()->create();
        $this->get('/login');
        $before = session()->getId();
        $this->post('/login', ['email' => ' '.strtoupper($user->email).' ', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_invalid_credentials_and_login_throttling(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_password_reset_notification_and_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => ' '.strtoupper($user->email).' '])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $this->post('/reset-password', ['token' => $notification->token, 'email' => ' '.strtoupper($user->email).' ', 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'])->assertSessionHasNoErrors();

            return Hash::check('replacement-password', $user->fresh()->password);
        });
    }
}
