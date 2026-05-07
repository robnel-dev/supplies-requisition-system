<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Requestor\CatalogController;
use App\Http\Controllers\Requestor\CartController;

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

    // ==========================================
    // SHARED ROUTES (All authenticated users)
    // ==========================================
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');


    // ==========================================
    // HR ADMIN ONLY ROUTES
    // ==========================================
    Route::middleware('role:hr_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // Department Management
            Route::resource('departments', DepartmentController::class)
                ->except(['create', 'show', 'edit']);

            // User Management
            Route::put('users/{user}/password', [UserController::class, 'updatePassword'])
                ->name('users.password');
            Route::resource('users', UserController::class)
                ->except(['create', 'show', 'edit']);

            // Supplies Management
            // NOTE: Custom routes MUST be defined BEFORE the resource route to avoid
            // route conflicts with {supply} wildcard catching 'search-external'.
            Route::get('supplies/search-external', [SupplyController::class, 'searchExternal'])
                ->name('supplies.search-external');
            Route::patch('supplies/{supply}/toggle-status', [SupplyController::class, 'toggleStatus'])
                ->name('supplies.toggle-status');
            Route::resource('supplies', SupplyController::class)
                ->except(['create', 'show', 'edit']);
        });


    // ==========================================
    // REQUESTOR ONLY ROUTES
    // ==========================================
    Route::middleware('role:requestor')
        ->prefix('requestor')
        ->name('requestor.')
        ->group(function () {

            Route::get('/catalog', [CatalogController::class, 'index'])
                ->name('catalog.index');

            Route::post('/cart', [CartController::class, 'store'])
                ->name('cart.store');
            Route::put('/cart/{itemId}', [CartController::class, 'update'])
                ->name('cart.update');
            Route::delete('/cart/{itemId}', [CartController::class, 'destroy'])
                ->name('cart.destroy');
            Route::post('/cart/checkout', [CartController::class, 'checkout'])
                ->name('cart.checkout');
        });


    // ==========================================
    // APPROVER ONLY ROUTES (Phase 3)
    // ==========================================
    Route::middleware('role:approver')
        ->prefix('approver')
        ->name('approver.')
        ->group(function () {
            // Placeholder — approval routes go here in Phase 3
        });
});

require __DIR__ . '/auth.php';
