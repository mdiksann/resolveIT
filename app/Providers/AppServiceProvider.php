<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => $user->role === Role::Admin);
        Password::defaults(fn () => Password::min(12));
    }
}
