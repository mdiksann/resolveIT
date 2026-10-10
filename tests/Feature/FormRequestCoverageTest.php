<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Each application Form Request has an HTTP validation-failure assertion. */
class FormRequestCoverageTest extends TestCase
{
    use RefreshDatabase;

    public static function requests(): array
    {
        return [
            'TicketIndexRequest' => ['GET', '/tickets', ['search' => str_repeat('x', 101), 'status' => 'invalid', 'sort' => 'id', 'page' => 0, 'per_page' => 100], ['search', 'status', 'sort', 'page', 'per_page']],
            'TicketShowRequest' => ['GET', '/tickets/{ticket}', ['comments_page' => 0, 'attachments_page' => -1, 'activities_page' => 'bad'], ['comments_page', 'attachments_page', 'activities_page']],
            'StoreTicketRequest' => ['POST', '/tickets', ['title' => 'ab', 'description' => 'short', 'priority_id' => 999999, 'category_id' => 999999], ['title', 'description', 'priority_id', 'category_id']],
            'UpdateTicketRequest' => ['PATCH', '/tickets/{ticket}', ['title' => 'ab', 'description' => 'short', 'priority_id' => 999999, 'category_id' => 999999], ['title', 'description', 'priority_id', 'category_id']],
            'StoreCommentRequest' => ['POST', '/tickets/{ticket}/comments', ['body' => ' ', 'is_internal' => 'bad'], ['body', 'is_internal']],
            'StoreAttachmentRequest' => ['POST', '/tickets/{ticket}/attachments', [], ['file']],
            'DeleteAttachmentRequest' => ['DELETE', '/tickets/{ticket}/attachments/{attachment}', ['unknown' => true], ['unknown']],
            'AssignTicketRequest' => ['PATCH', '/tickets/{ticket}/assignment', ['assignee_id' => 999999], ['assignee_id']],
            'TicketAssignmentRequest' => ['POST', '/tickets/{ticket}/self-assign', ['unknown' => true], ['unknown']],
            'TransitionTicketRequest' => ['PATCH', '/tickets/{ticket}/status', ['status' => 'invalid'], ['status']],
            'DashboardRequest' => ['GET', '/dashboard', ['assignments_page' => 0], ['assignments_page']],
            'UpdateProfileRequest' => ['PATCH', '/settings/profile', ['name' => '', 'email' => 'invalid'], ['name', 'email']],
            'ConfigurationIndexRequest' => ['GET', '/admin/categories', ['page' => 0], ['page']],
            'StoreCategoryRequest' => ['POST', '/admin/categories', ['name' => 'x'], ['name']],
            'UpdateCategoryRequest' => ['PATCH', '/admin/categories/{category}', ['name' => 'x'], ['name']],
            'DeactivateCategoryRequest' => ['PATCH', '/admin/categories/{category}/deactivate', ['unknown' => true], ['unknown']],
            'StorePriorityRequest' => ['POST', '/admin/priorities', ['name' => 'x', 'rank' => 0, 'sla_hours' => 721, 'is_default' => 'bad'], ['name', 'rank', 'sla_hours', 'is_default']],
            'UpdatePriorityRequest' => ['PATCH', '/admin/priorities/{priority}', ['name' => 'x', 'rank' => 0, 'sla_hours' => 0, 'is_default' => 'bad'], ['name', 'rank', 'sla_hours', 'is_default']],
            'PriorityActionRequest' => ['PATCH', '/admin/priorities/{priority}/default', ['unknown' => true], ['unknown']],
            'UserIndexRequest' => ['GET', '/admin/users', ['page' => 0], ['page']],
            'UpdateUserRoleRequest' => ['PATCH', '/admin/users/{user}/role', ['role' => 'invalid'], ['role']],
        ];
    }

    public function test_every_form_request_has_a_validation_case(): void
    {
        $actual = [];
        foreach (glob(app_path('Http/Requests/*.php')) as $file) {
            $name = basename($file, '.php');
            if (is_subclass_of('App\\Http\\Requests\\'.$name, FormRequest::class)) {
                $actual[] = $name;
            }
        }
        $expected = array_keys(self::requests());
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual, 'Add an HTTP validation case for every new Form Request.');
    }

    #[DataProvider('requests')]
    public function test_invalid_input_never_reaches_persistence(string $method, string $path, array $input, array $fields): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => Role::Admin]);
        $ticket = Ticket::factory()->create(['requester_id' => $admin->id]);
        $attachment = TicketAttachment::factory()->create(['ticket_id' => $ticket->id, 'uploader_id' => $admin->id]);
        $category = Category::factory()->create();
        $priority = Priority::factory()->create();
        $other = User::factory()->create();
        $path = strtr($path, ['{ticket}' => $ticket->id, '{attachment}' => $attachment->id, '{category}' => $category->id, '{priority}' => $priority->id, '{user}' => $other->id]);
        $before = [$ticket->fresh()->getRawOriginal(), $category->fresh()->getRawOriginal(), $priority->fresh()->getRawOriginal(), $other->fresh()->getRawOriginal()];
        $this->actingAs($admin)->json($method, $path, $input)->assertUnprocessable()->assertJsonValidationErrors($fields);
        $this->assertSame($before, [$ticket->fresh()->getRawOriginal(), $category->fresh()->getRawOriginal(), $priority->fresh()->getRawOriginal(), $other->fresh()->getRawOriginal()]);
        $this->assertDatabaseCount('ticket_activities', 0);
        $this->assertDatabaseCount('ticket_comments', 0);
        $this->assertDatabaseCount('ticket_attachments', 1);
    }
}
