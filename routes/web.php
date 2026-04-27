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

Route::middleware(['auth'])->group(function () {

    // General Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
     * --------------------------------------------------------------------
     * HR Admin Module Routes
     * --------------------------------------------------------------------
     */
    Route::prefix('admin')->name('admin.')->group(function () {
        
        // Department Management (Creates .index and .store routes)
        Route::resource('departments', DepartmentController::class)->only(['index', 'store']);

        // User Management (Creates .index and .store routes)
        Route::resource('users', UserController::class)->only(['index', 'store']);
        
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';