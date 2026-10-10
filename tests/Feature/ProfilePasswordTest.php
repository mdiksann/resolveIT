<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_change_password(): void
    {
        $this->put('/settings/password')->assertRedirect('/login');
    }

    public function test_password_change_only_updates_the_authenticated_user_and_accepts_the_new_login(): void
    {
        $user = User::factory()->create(['password' => 'current-password']);
        $other = User::factory()->create(['password' => 'other-password']);
        $this->actingAs($user)->from('/settings/profile')->put('/settings/password', [
            'current_password' => 'current-password',
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
            'user_id' => $other->id,
            'role' => 'ADMIN',
        ])->assertRedirect('/settings/profile')->assertSessionHasNoErrors()->assertSessionHas('success', 'Password updated.');
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('current-password', $user->password));
        $this->assertTrue(Hash::check('other-password', $other->fresh()->password));
        $this->assertSame('EMPLOYEE', $user->role->value);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'current-password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'replacement-password'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_password_changes_leave_the_existing_password_unchanged(array $payload, string $error): void
    {
        $user = User::factory()->create(['password' => 'current-password']);
        $this->actingAs($user)->put('/settings/password', $payload)
            ->assertSessionHasErrors($error)
            ->assertSessionMissing('_old_input.current_password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
        $this->assertTrue(Hash::check('current-password', $user->fresh()->password));
    }

    public static function invalidPasswords(): array
    {
        $valid = ['current_password' => 'current-password', 'password' => 'replacement-password', 'password_confirmation' => 'replacement-password'];

        return [
            'wrong current password' => [array_replace($valid, ['current_password' => 'wrong-password']), 'current_password'],
            'missing current password' => [array_replace($valid, ['current_password' => '']), 'current_password'],
            'short new password' => [array_replace($valid, ['password' => 'short', 'password_confirmation' => 'short']), 'password'],
            'confirmation mismatch' => [array_replace($valid, ['password_confirmation' => 'different-password']), 'password'],
            'malformed current password' => [array_replace($valid, ['current_password' => ['unexpected']]), 'current_password'],
        ];
    }
}
