<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuration_action_role_matrix(): void
    {
        foreach ([null, ...Role::cases()] as $role) {
            auth()->logout();
            if ($role) {
                $this->actingAs(User::factory()->create(['role' => $role]));
            }
            $category = Category::factory()->create();
            $responses = [
                $this->get('/admin/categories'),
                $this->post('/admin/categories', ['name' => 'new category '.($role?->value ?? 'guest')]),
                $this->patch('/admin/categories/'.$category->id, ['name' => 'renamed '.($role?->value ?? 'guest')]),
                $this->patch('/admin/categories/'.$category->id.'/deactivate'),
            ];
            foreach ($responses as $index => $response) {
                if (! $role) {
                    $response->assertRedirect('/login');
                } elseif ($role !== Role::Admin) {
                    $response->assertForbidden();
                } else {
                    $response->assertStatus($index === 0 ? 200 : 302);
                }
            }
            $this->assertSame($role !== Role::Admin, $category->fresh()->is_active);
        }
    }

    public function test_create_rename_normalization_uniqueness_and_invalid_input(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->post('/admin/categories', ['name' => ' VPN '])->assertSessionHas('success');
        $category = Category::sole();
        $this->assertSame('vpn', $category->name);
        $this->patch('/admin/categories/'.$category->id, ['name' => 'VPN'])->assertSessionHasNoErrors();
        $legacy = Category::factory()->create(['name' => 'Network']);
        foreach ([['name' => 'vPn'], ['name' => ' NETWORK '], ['name' => 'x'], ['name' => str_repeat('a', 61)], ['name' => []], ['name' => null], ['name' => 'valid', 'is_active' => false]] as $data) {
            $this->postJson('/admin/categories', $data)->assertUnprocessable();
        }
        $this->patchJson('/admin/categories/'.$category->id, ['name' => $legacy->name])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->patch('/admin/categories/'.$category->id, ['name' => 'Hardware'])->assertSessionHasNoErrors();
        $this->assertSame('hardware', $category->fresh()->name);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_referenced_categories_can_retire_without_losing_ticket_metadata(): void
    {
        $ticket = Ticket::factory()->create();
        $category = $ticket->category;
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->patch('/admin/categories/'.$category->id.'/deactivate')->assertSessionHas('success');
        $this->patch('/admin/categories/'.$category->id.'/deactivate')->assertSessionHasNoErrors();
        $this->get('/tickets/create')->assertInertia(fn (Assert $p) => $p->has('categories', 0));
        $this->get('/tickets/'.$ticket->id)->assertInertia(fn (Assert $p) => $p->where('ticket.category.name', $category->name));
        $this->assertSame($category->id, $ticket->fresh()->category_id);
        $this->assertDatabaseCount('categories', 1);
        $this->postJson('/tickets', ['title' => 'Invalid category', 'description' => 'Cannot use retired category', 'category_id' => $category->id, 'priority_id' => $ticket->priority_id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_pagination_unknown_fields_and_missing_record(): void
    {
        Category::factory()->count(23)->create();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/categories')->assertInertia(fn (Assert $p) => $p->component('Admin/Categories')->has('categories.data', 20)->where('categories.total', 23)->where('categories.per_page', 20));
        $this->get('/admin/categories?page=2')->assertInertia(fn (Assert $p) => $p->has('categories.data', 3));
        $this->getJson('/admin/categories?page=0')->assertUnprocessable();
        $this->getJson('/admin/categories?sort=name')->assertUnprocessable();
        $category = Category::first();
        $this->patchJson('/admin/categories/'.$category->id.'/deactivate', ['name' => 'Injected'])->assertUnprocessable();
        $this->patch('/admin/categories/999999', ['name' => 'Missing'])->assertNotFound();
    }
}
