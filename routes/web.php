<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/login');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // SHARED ROUTES (Available to everyone logged in)
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');

    // HR ADMIN ONLY ROUTES
    Route::middleware(['role:hr_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('departments', DepartmentController::class);
            
            // Custom route for updating passwords MUST go before the resource route
            Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password');
            Route::resource('users', UserController::class);
        });

    // APPROVER ONLY ROUTES
    Route::middleware(['role:approver'])->group(function () {
        // Route::get('/approvals', ...);
    });
});

require __DIR__ . '/auth.php';