<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Requestor\CatalogController;
use App\Http\Controllers\Requestor\CartController;
use App\Http\Controllers\Requestor\RequestController;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {

    // ─── Shared (all roles) ───────────────────────────────────────────────
    Route::get('/dashboard', fn() => inertia('Dashboard'))->name('dashboard');

    // ─── HR Admin ────────────────────────────────────────────────────────
    Route::middleware('role:hr_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('departments', DepartmentController::class)
                ->except(['create', 'show', 'edit']);

            Route::put('users/{user}/password', [UserController::class, 'updatePassword'])
                ->name('users.password');
            Route::resource('users', UserController::class)
                ->except(['create', 'show', 'edit']);

            // Custom routes BEFORE resource to avoid wildcard conflict
            Route::get('supplies/search-external', [SupplyController::class, 'searchExternal'])
                ->name('supplies.search-external');
            Route::patch('supplies/{supply}/toggle-status', [SupplyController::class, 'toggleStatus'])
                ->name('supplies.toggle-status');
            Route::resource('supplies', SupplyController::class)
                ->except(['create', 'show', 'edit']);
        });

    // ─── Requestor ───────────────────────────────────────────────────────
    Route::middleware('role:requestor')
        ->prefix('requestor')
        ->name('requestor.')
        ->group(function () {

            // Supplies Catalog
            Route::get('/catalog', [CatalogController::class, 'index'])
                ->name('catalog.index');

            // Cart (draft request)
            Route::post('/cart', [CartController::class, 'store'])
                ->name('cart.store');
            Route::put('/cart/{itemId}', [CartController::class, 'update'])
                ->name('cart.update');
            Route::delete('/cart/{itemId}', [CartController::class, 'destroy'])
                ->name('cart.destroy');
            Route::post('/cart/checkout', [CartController::class, 'checkout'])
                ->name('cart.checkout');

            // Active Requests
            Route::get('/requests', [RequestController::class, 'index'])
                ->name('requests.index');
            Route::get('/requests/{supplyRequest}', [RequestController::class, 'show'])
                ->name('requests.show');
            Route::patch('/requests/{supplyRequest}/cancel', [RequestController::class, 'cancel'])
                ->name('requests.cancel');
        });

    // ─── Approver (Phase 3) ───────────────────────────────────────────────
    Route::middleware('role:approver')
        ->prefix('approver')
        ->name('approver.')
        ->group(function () {
            // Phase 3 routes go here
        });
});

require __DIR__ . '/auth.php';
