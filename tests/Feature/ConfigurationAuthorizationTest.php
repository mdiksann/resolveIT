<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConfigurationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Authorization probes only: configuration CRUD remains in TASK-021/022.
        foreach (['categories' => Category::class, 'priorities' => Priority::class] as $path => $model) {
            Route::middleware(['web', 'auth'])->post('/test/'.$path, function () use ($model) {
                Gate::authorize('create', $model);

                return response()->noContent();
            });
            Route::middleware(['web', 'auth'])->patch('/test/'.$path, function () use ($model) {
                Gate::authorize('update', new $model);

                return response()->noContent();
            });
        }

    }

    #[DataProvider('actors')]
    public function test_configuration_mutation_authorization(?Role $role): void
    {
        if ($role !== null) {
            $this->actingAs(User::factory()->create(['role' => $role]));
        }
        foreach (['categories', 'priorities'] as $path) {
            foreach (['post', 'patch'] as $method) {
                $response = $this->$method('/test/'.$path);
                if ($role === null) {
                    $response->assertRedirect('/login');
                } else {
                    $response->assertStatus($role === Role::Admin ? 204 : 403);
                }
            }
        }
    }

    public static function actors(): array
    {
        return [[null], [Role::Employee], [Role::Agent], [Role::Admin]];
    }

    public function test_last_admin_self_demotion_returns_field_validation_error(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->patchJson('/admin/users/'.$admin->id.'/role', ['role' => Role::Employee->value])
            ->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }
}
