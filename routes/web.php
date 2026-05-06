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
    // SHARED ROUTES (Available to everyone logged in)
    // ==========================================
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');


    // ==========================================
    // HR ADMIN ONLY ROUTES
    // ==========================================
    Route::middleware(['role:hr_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            // --- Department Management ---
            Route::resource('departments', DepartmentController::class)
                ->except(['create', 'show', 'edit']);

            // --- User Management ---
            // Custom route for updating passwords MUST go before the resource route
            Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password');
            Route::resource('users', UserController::class)
                ->except(['create', 'show', 'edit']);


            // --- Supplies Management ---
            // Custom endpoints go ABOVE the resource route

            // Auto-fill API Route
            Route::get('/supplies/search-external', [SupplyController::class, 'searchExternal'])->name('supplies.search-external');

            // Status Toggle Route
            Route::patch('/supplies/{supply}/toggle-status', [SupplyController::class, 'toggleStatus'])->name('supplies.toggle-status');

            // Standard CRUD (This automatically generates Index, Store, Update, and Destroy)
            Route::resource('supplies', SupplyController::class)->except(['create', 'show', 'edit']);
        });

    // ==========================================
    // REQUESTOR ONLY ROUTES
    // ==========================================
    Route::middleware(['auth', 'role:requestor'])->prefix('requestor')->name('requestor.')->group(function () {

        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');

        Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::put('/cart/{itemId}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/{itemId}', [CartController::class, 'destroy'])->name('cart.destroy');
        Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    });



    // ==========================================
    // APPROVER ONLY ROUTES
    // ==========================================
    Route::middleware(['role:approver'])->group(function () {
        // Route::get('/approvals', ...);
    });
});

require __DIR__ . '/auth.php';
