<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->can('manage-tickets') ? 'dashboard' : 'profile.edit');
})->name('home');
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->middleware('can:manage-tickets')->name('dashboard');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::prefix('admin')->name('admin.')->middleware('can:administer')->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.users.index'))->name('index');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    });
});
