<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn (Request $request) => Inertia::render('Auth/Login', ['status' => $request->session()->get('status')]));
        Fortify::registerView(fn () => Inertia::render('Auth/Register'));
        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('Auth/ForgotPassword', ['status' => $request->session()->get('status')]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', ['email' => $request->email, 'token' => $request->route('token')]));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(
            (is_string($request->input('email')) ? Str::lower($request->input('email')) : '').'|'.$request->ip()
        ));
        Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));
        RateLimiter::for('auth-write', fn (Request $request) => $request->isMethod('POST') && in_array($request->path(), ['register', 'forgot-password', 'reset-password'])
                ? Limit::perMinute(10)->by($request->path().'|'.$request->ip())
                : Limit::none()
        );
    }
}
