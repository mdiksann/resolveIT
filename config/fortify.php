<?php

use App\Http\Middleware\NormalizeAuthEmail;
use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    // Normalize strings before Fortify without coercing malformed input.
    'lowercase_usernames' => false,
    'home' => '/',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web', NormalizeAuthEmail::class, 'throttle:auth-write'],
    'limiters' => ['login' => 'login'],
    'views' => true,
    'features' => [Features::registration(), Features::resetPasswords()],
];
