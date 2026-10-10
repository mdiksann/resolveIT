<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->can('manage-tickets') ? 'dashboard' : 'profile.edit');
    }

    return Inertia::render('Landing');
})->name('home');

Route::get('/landing', fn () => Inertia::render('Landing'))->name('landing');
Route::middleware('auth')->group(function (): void {
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])->name('tickets.attachments.store');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'show'])->scopeBindings()->name('tickets.attachments.show');
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy'])->scopeBindings()->name('tickets.attachments.destroy');
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::patch('/tickets/{ticket}/assignment', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::post('/tickets/{ticket}/self-assign', [TicketController::class, 'selfAssign'])->name('tickets.self-assign');
    Route::delete('/tickets/{ticket}/assignment', [TicketController::class, 'unassign'])->name('tickets.unassign');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'transition'])->name('tickets.transition');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('profile.password.update');
    Route::prefix('admin')->name('admin.')->middleware('can:administer')->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.users.index'))->name('index');
        Route::get('/priorities', [PriorityController::class, 'index'])->name('priorities.index');
        Route::post('/priorities', [PriorityController::class, 'store'])->name('priorities.store');
        Route::patch('/priorities/{priority}', [PriorityController::class, 'update'])->name('priorities.update');
        Route::patch('/priorities/{priority}/deactivate', [PriorityController::class, 'deactivate'])->name('priorities.deactivate');
        Route::patch('/priorities/{priority}/default', [PriorityController::class, 'setDefault'])->name('priorities.default');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/deactivate', [CategoryController::class, 'deactivate'])->name('categories.deactivate');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    });
});
