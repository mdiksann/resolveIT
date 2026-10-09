<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return [...parent::share($request),
            'appName' => config('app.name'),
            'timeZone' => config('app.timezone'),
            'auth' => [
                'user' => $user?->only('id', 'name', 'role'),
                'canAccessAdmin' => $user?->can('access-admin') ?? false,
                'can' => [
                    'viewAnyTicket' => $user?->can('manage-tickets') ?? false,
                    'manageCategories' => $user?->can('viewAny', Category::class) ?? false,
                    'managePriorities' => $user?->can('viewAny', Priority::class) ?? false,
                    'manageUsers' => $user?->can('viewAny', User::class) ?? false,
                ],
            ],
            'flash' => fn () => collect(['success', 'error', 'warning', 'info'])->mapWithKeys(fn ($key) => [$key => $request->session()->get($key)])->all(),
        ];
    }
}
