<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\UserIndexRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(UserIndexRequest $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Admin', [
            'users' => User::query()->select('id', 'name', 'email', 'role')
                ->withCount(['requestedTickets', 'assignedTickets'])->orderBy('id')->paginate(20),
            'roles' => array_map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()], Role::cases()),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = Role::from($request->validated('role'));
        $change = DB::transaction(function () use ($request, $user, $role): ?array {
            // Serialize role changes before checking the last-admin invariant.
            User::where('role', Role::Admin)->orderBy('id')->lockForUpdate()->get(['id']);
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $actor = User::findOrFail($request->user()->id);
            Gate::forUser($actor)->authorize('changeRole', [$target, $role]);
            if ($target->role === $role) {
                return null;
            }

            $previous = $target->role;
            $target->role = $role;
            $target->save();

            return ['actor_id' => $actor->id, 'user_id' => $target->id, 'from' => $previous->value, 'to' => $role->value];
        });
        if ($change !== null) {
            Log::info('User role changed.', $change);
        }

        return back()->with('success', $change === null ? 'Role is already up to date.' : 'User role updated.');
    }
}
