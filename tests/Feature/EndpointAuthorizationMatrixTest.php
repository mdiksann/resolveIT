<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Route matrix (every routes/web.php endpoint, checked against the router below):
 * Public: home/landing. Authenticated: profile and ticket list/create/store.
 * Ticket records: employee owner only; agent/admin any requester. Employee may
 * close/reopen resolved tickets, but never assign/unassign/self-assign.
 * Attachments: download with ticket access; removal by uploader or admin.
 * Dashboard: employee redirects to own queue, staff allowed. Admin: admin only.
 * Each endpoint runs as guest/employee/agent/admin, with own AND foreign records.
 * Fortify's vendor routes are covered in AuthenticationTest/SessionSecurityTest.
 * Adding an app route without adding a matrix row fails the coverage assertion.
 */
class EndpointAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public static function routes(): array
    {
        return [
            'home' => ['GET', '/', 'public'],
            'landing' => ['GET', '/landing', 'public'],
            'tickets.index' => ['GET', '/tickets', 'auth'],
            'tickets.create' => ['GET', '/tickets/create', 'auth'],
            'tickets.store' => ['POST', '/tickets', 'auth'],
            'tickets.show' => ['GET', '/tickets/{ticket}', 'record'],
            'tickets.edit' => ['GET', '/tickets/{ticket}/edit', 'record'],
            'tickets.update' => ['PATCH', '/tickets/{ticket}', 'record'],
            'tickets.comments.store' => ['POST', '/tickets/{ticket}/comments', 'record'],
            'tickets.attachments.store' => ['POST', '/tickets/{ticket}/attachments', 'record'],
            'tickets.attachments.show' => ['GET', '/tickets/{ticket}/attachments/{attachment}', 'record'],
            'tickets.attachments.destroy' => ['DELETE', '/tickets/{ticket}/attachments/{attachment}', 'record'],
            'tickets.assign' => ['PATCH', '/tickets/{ticket}/assignment', 'staff'],
            'tickets.self-assign' => ['POST', '/tickets/{ticket}/self-assign', 'staff'],
            'tickets.unassign' => ['DELETE', '/tickets/{ticket}/assignment', 'staff'],
            'tickets.transition' => ['PATCH', '/tickets/{ticket}/status', 'record'],
            'dashboard' => ['GET', '/dashboard', 'auth'],
            'profile.edit' => ['GET', '/settings/profile', 'auth'],
            'profile.update' => ['PATCH', '/settings/profile', 'auth'],
            'profile.password.update' => ['PUT', '/settings/password', 'auth'],
            'admin.index' => ['GET', '/admin', 'admin'],
            'admin.categories.index' => ['GET', '/admin/categories', 'admin'],
            'admin.categories.store' => ['POST', '/admin/categories', 'admin'],
            'admin.categories.update' => ['PATCH', '/admin/categories/{category}', 'admin'],
            'admin.categories.deactivate' => ['PATCH', '/admin/categories/{category}/deactivate', 'admin'],
            'admin.priorities.index' => ['GET', '/admin/priorities', 'admin'],
            'admin.priorities.store' => ['POST', '/admin/priorities', 'admin'],
            'admin.priorities.update' => ['PATCH', '/admin/priorities/{priority}', 'admin'],
            'admin.priorities.deactivate' => ['PATCH', '/admin/priorities/{priority}/deactivate', 'admin'],
            'admin.priorities.default' => ['PATCH', '/admin/priorities/{priority}/default', 'admin'],
            'admin.users.index' => ['GET', '/admin/users', 'admin'],
            'admin.users.update-role' => ['PATCH', '/admin/users/{user}/role', 'admin'],
        ];
    }

    public function test_every_application_route_has_a_matrix_entry(): void
    {
        $actual = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }
            // Fortify has its own auth/security suite.
            if (str_starts_with($route->getActionName(), 'Laravel\\Fortify\\')) {
                continue;
            }
            $actual[$route->getName()] = [$route->methods()[0], '/'.ltrim($route->uri(), '/')];
        }
        $expected = array_map(fn ($row) => array_slice($row, 0, 2), self::routes());
        ksort($actual);
        ksort($expected);
        $this->assertSame($expected, $actual);
    }

    public static function matrix(): array
    {
        $cases = [];
        foreach (self::routes() as $name => [$method, $path, $access]) {
            foreach ([null, ...Role::cases()] as $role) {
                foreach (str_contains($path, '{ticket}') ? [true, false] : [true] as $own) {
                    $cases[$name.'-'.($role?->value ?? 'guest').'-'.($own ? 'own' : 'foreign')] = [$name, $method, $path, $access, $role, $own];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_endpoint_role_and_ownership_matrix(string $name, string $method, string $path, string $access, ?Role $role, bool $own): void
    {
        Notification::fake();
        Storage::fake('local');
        $actor = User::factory()->create(['role' => $role ?? Role::Employee]);
        $agent = User::factory()->create(['role' => Role::Agent]);
        $other = User::factory()->create();
        $priority = Priority::factory()->create();
        Priority::factory()->create(['is_default' => true]);
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create([
            'requester_id' => $own ? $actor->id : $other->id,
            'status' => $name === 'tickets.transition' ? TicketStatus::Resolved : TicketStatus::Open,
        ]);
        $attachment = TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'uploader_id' => $actor->id, 'disk' => 'local', 'path' => 'matrix.txt']);
        Storage::disk('local')->put('matrix.txt', 'Private matrix fixture');
        $path = strtr($path, ['{ticket}' => $ticket->id, '{attachment}' => $attachment->id, '{category}' => $category->id, '{priority}' => $priority->id, '{user}' => $other->id]);
        $data = match ($name) {
            'tickets.store', 'tickets.update' => ['title' => 'Matrix ticket', 'description' => 'A valid helpdesk request for the matrix.', 'priority_id' => $priority->id],
            'tickets.comments.store' => ['body' => 'Public matrix comment'],
            'tickets.attachments.store' => ['file' => UploadedFile::fake()->createWithContent('matrix.txt', 'Attachment fixture')],
            'tickets.assign' => ['assignee_id' => $agent->id],
            'tickets.transition' => ['status' => TicketStatus::Closed->value],
            'profile.update' => ['name' => 'Updated name', 'email' => $actor->email],
            'profile.password.update' => ['current_password' => 'password', 'password' => 'changed-password', 'password_confirmation' => 'changed-password'],
            'admin.categories.store', 'admin.categories.update' => ['name' => 'matrix category'],
            'admin.priorities.store', 'admin.priorities.update' => ['name' => 'matrix priority', 'rank' => 2, 'sla_hours' => 12],
            'admin.users.update-role' => ['role' => Role::Agent->value],
            default => [],
        };
        if ($role !== null) {
            $this->actingAs($actor);
        }
        $response = $this->call($method, $path, $data);
        if ($role === null && $access !== 'public') {
            $response->assertRedirect('/login');
        } elseif (($access === 'admin' && $role !== Role::Admin)
            || ($access === 'staff' && $role === Role::Employee)
            || ($access === 'record' && $role === Role::Employee && ! $own)) {
            $response->assertStatus($name === 'tickets.show' ? 404 : 403);
        } elseif (in_array($name, ['home', 'admin.index'], true) && $role !== null) {
            $response->assertRedirect();
        } elseif ($name === 'dashboard' && $role === Role::Employee) {
            $response->assertRedirect('/tickets');
        } elseif ($method === 'GET') {
            $response->assertOk();
        } else {
            $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        }
    }
}
