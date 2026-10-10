<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteGuardTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('guardedRoutes')]
    public function test_route_access_matrix(?Role $role, string $path, int $status): void
    {
        if ($role !== null) {
            $this->actingAs(User::factory()->create(['role' => $role]));
        }
        $response = $this->get($path)->assertStatus($status);
        if ($role === null) {
            $response->assertRedirect('/login');
        }
    }

    public static function guardedRoutes(): array
    {
        $cases = [];
        foreach ([null, ...Role::cases()] as $role) {
            foreach (['/dashboard', '/admin', '/settings/profile'] as $path) {
                $status = $role === null ? 302 : match ($path) {
                    '/dashboard' => $role === Role::Employee ? 302 : 200,
                    '/admin' => $role === Role::Admin ? 302 : 403,
                    default => 200,
                };
                $cases[] = [$role, $path, $status];
            }
        }

        return $cases;
    }

    public function test_guest_cannot_mutate_an_authenticated_profile(): void
    {
        $this->patch('/settings/profile', ['name' => 'Guest', 'email' => 'guest@example.test'])->assertRedirect('/login');
    }

    public function test_home_redirects_to_an_area_the_user_can_access(): void
    {
        $this->get('/')->assertOk();
        $this->get('/landing')->assertOk();
        foreach (Role::cases() as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/')->assertRedirect($role === Role::Employee ? '/settings/profile' : '/dashboard');
        }
    }
}
