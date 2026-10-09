<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\TicketEvent;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $accounts = [
            'admin' => Role::Admin, 'agent' => Role::Agent, 'agent2' => Role::Agent,
            'employee' => Role::Employee, 'employee2' => Role::Employee, 'employee3' => Role::Employee,
            'employee4' => Role::Employee, 'employee5' => Role::Employee,
        ];
        $users = [];
        foreach ($accounts as $name => $role) {
            $user = User::firstOrNew(['email' => $name.'@example.test']);
            if (! $user->exists) {
                $user->fill(['name' => ucfirst($name), 'password' => 'local-password']);
                $user->role = $role;
                $user->save();
            }
            $users[$name] = $user;
        }
        $categories = collect(['network', 'hardware', 'software', 'accounts', 'email', 'access'])
            ->map(fn ($name) => Category::firstOrCreate(['name' => $name], ['is_active' => true]));
        $priorities = DB::transaction(function () {
            // Use the same serialization as admin priority writes; preserve an existing default.
            DB::statement('LOCK TABLE priorities IN SHARE ROW EXCLUSIVE MODE');
            $items = collect([
                ['urgent', 1, 4], ['high', 2, 8], ['normal', 3, 24], ['low', 4, 72],
            ])->map(fn ($p) => Priority::firstOrCreate(['name' => $p[0]], ['rank' => $p[1], 'sla_hours' => $p[2], 'is_active' => true, 'is_default' => false]));
            if (! Priority::where('is_active', true)->where('is_default', true)->exists()) {
                Priority::where('is_default', true)->update(['is_default' => false]);
                $default = $items->first(fn ($p) => $p->name === 'normal' && $p->is_active)
                    ?? $items->first(fn ($p) => $p->is_active);
                if (! $default) {
                    throw new RuntimeException('Demo seeding requires an active priority.');
                }
                $default->update(['is_default' => true]);
            }

            return $items;
        });
        $employees = collect($users)->filter(fn ($user, $name) => str_starts_with($name, 'employee'))->values();
        $agents = collect($users)->filter(fn ($user) => in_array($user->role, [Role::Agent, Role::Admin], true))->values();
        if ($agents->isEmpty()) {
            throw new RuntimeException('Demo seeding requires at least one staff account; existing roles are preserved.');
        }
        $subjects = ['VPN connection fails', 'Laptop needs repair', 'Install design software', 'Reset account access', 'Email delivery delayed', 'Request shared folder access'];
        $now = now();
        for ($i = 0; $i < 40; $i++) {
            $title = sprintf('[Demo %02d] %s', $i + 1, $subjects[$i % count($subjects)]);
            $requester = $employees[$i % $employees->count()];
            if (Ticket::where('title', $title)->where('requester_id', $requester->id)->exists()) {
                continue;
            }
            $status = TicketStatus::cases()[intdiv($i, 8)];
            $priority = $priorities[$i % $priorities->count()];
            $createdAt = $now->copy()->subHours($i % 2 === 0 ? 120 + $i : 1);
            if ($i >= 24 && $i % 3 === 0) {
                $createdAt = $now->copy()->subDays(12);
            }
            $agent = $agents[$i % $agents->count()];
            Carbon::withTestNow($createdAt, function () use ($title, $requester, $priority, $categories, $i, $status, $agent, $createdAt): void {
                DB::transaction(function () use ($title, $requester, $priority, $categories, $i, $status, $agent, $createdAt): void {
                    $ticket = Ticket::create([
                        'title' => $title,
                        'description' => 'Demo helpdesk request: '.substr($title, 10).'. Please investigate and provide an update. This is fictional local sample data.',
                        'requester_id' => $requester->id, 'category_id' => $categories[$i % $categories->count()]->id,
                        'priority_id' => $priority->id, 'status' => TicketStatus::Open,
                        'due_at' => $createdAt->copy()->addHours($priority->sla_hours),
                    ]);
                    TicketActivity::record($ticket, TicketEvent::Created, $requester);
                    if ($status !== TicketStatus::Open) {
                        $ticket->assignTo($agent, $agent);
                    }
                    if (in_array($status, [TicketStatus::InProgress, TicketStatus::Resolved, TicketStatus::Closed], true)) {
                        $ticket->transitionTo(TicketStatus::InProgress, $agent);
                    }
                    $comment = $ticket->comments()->create(['author_id' => $requester->id, 'body' => 'I can reproduce this issue. Please let me know if you need more details.', 'is_internal' => false]);
                    TicketActivity::record($ticket, TicketEvent::Commented, $requester, ['comment_id' => $comment->id, 'is_internal' => false]);
                    if ($i % 3 === 0) {
                        $note = $ticket->comments()->create(['author_id' => $agent->id, 'body' => 'Internal: checked the service logs; follow up with the infrastructure team.', 'is_internal' => true]);
                        TicketActivity::record($ticket, TicketEvent::Commented, $agent, ['comment_id' => $note->id, 'is_internal' => true]);
                    }
                    if ($i % 8 === 0) {
                        $content = "ResolveIT fictional demo diagnostic\nNo personal or secret information.\n";
                        // Stable generated paths keep repeated fresh seeds from leaving orphan demo files.
                        $path = 'demo/'.hash('sha256', $title).'.txt';
                        if (! Storage::disk('local')->put($path, $content)) {
                            throw new RuntimeException('Unable to write the private demo attachment.');
                        }
                        $file = $ticket->attachments()->create(['uploader_id' => $requester->id, 'disk' => 'local', 'path' => $path, 'original_name' => 'demo-diagnostic.txt', 'mime_type' => 'text/plain', 'size_bytes' => strlen($content)]);
                        TicketActivity::record($ticket, TicketEvent::AttachmentAdded, $requester, ['attachment_id' => $file->id, 'name' => $file->original_name]);
                    }
                    if (in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
                        $ticket->transitionTo(TicketStatus::Resolved, $agent);
                    }
                    if ($status === TicketStatus::Closed) {
                        $ticket->transitionTo(TicketStatus::Closed, $requester);
                    }
                });
            });
        }
    }
}
