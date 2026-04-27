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

// Redirect root domain straight to the login page
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
    // We add ->name('admin.') so that 'departments' becomes 'admin.departments.index'
    // We add ->prefix('admin') so the URL becomes /admin/departments
    Route::middleware(['role:hr_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('departments', DepartmentController::class);
            Route::resource('users', UserController::class);
        });


    // APPROVER ONLY ROUTES
    Route::middleware(['role:approver'])->group(function () {
        // Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';