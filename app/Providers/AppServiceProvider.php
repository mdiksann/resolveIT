<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('administer', fn (User $user): bool => $user->can('viewAny', User::class));
        Gate::define('access-admin', fn (User $user): bool => $user->can('administer'));
        Gate::define('manage-tickets', fn (User $user): bool => $user->can('viewAny', Ticket::class));
        Password::defaults(fn () => Password::min(12));
    }
}
