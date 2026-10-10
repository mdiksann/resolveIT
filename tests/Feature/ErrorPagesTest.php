<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_403_renders_dedicated_inertia_error_page(): void
    {
        $employee = User::factory()->create(['role' => Role::Employee]);

        $this->actingAs($employee)
            ->get('/admin/users')
            ->assertStatus(403)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/403')
                ->where('status', 403));
    }

    public function test_404_renders_dedicated_inertia_error_page(): void
    {
        $this->get('/routes/that/do/not/exist/at/all')
            ->assertStatus(404)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/404')
                ->where('status', 404));
    }

    public function test_419_renders_dedicated_inertia_error_page(): void
    {
        Route::get('/_test/error-419', fn () => abort(419));

        $this->get('/_test/error-419')
            ->assertStatus(419)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/419')
                ->where('status', 419));
    }

    public function test_500_renders_dedicated_inertia_page_with_incident_reference_in_production(): void
    {
        config(['app.debug' => false]);

        Route::get('/_test/error-500', function () {
            throw new \RuntimeException('Simulated unexpected server error');
        });

        $this->get('/_test/error-500')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/500')
                ->where('status', 500)
                ->has('incidentId')
                ->missing('trace')
                ->missing('exception')
                ->missing('message'));
    }

    public function test_503_renders_dedicated_inertia_error_page(): void
    {
        Route::get('/_test/error-503', fn () => abort(503));

        $this->get('/_test/error-503')
            ->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/503')
                ->where('status', 503));
    }
}

