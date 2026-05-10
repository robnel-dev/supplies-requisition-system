<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SupplyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Requestor\CartController;
use App\Http\Controllers\Requestor\CatalogController;
use App\Http\Controllers\Approver\RequestController as ApproverRequestController;
use App\Http\Controllers\Requestor\RequestController as RequestorRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:hr_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('departments', DepartmentController::class)
                ->except(['create', 'show', 'edit']);

            // User creation loads store references after an area department is selected.  AJAX Endpoints
            Route::get('departments/store-refs/{area}', [DepartmentController::class, 'storeRefsByArea'])
                ->name('departments.store-refs');

            Route::put('users/{user}/password', [UserController::class, 'updatePassword'])
                ->name('users.password');
            Route::resource('users', UserController::class)
                ->except(['create', 'show', 'edit']);

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

            // Supply Requests
            Route::get('/requests', [RequestorRequestController::class, 'index'])
                ->name('requests.index');
            Route::get('/requests/archived', [RequestorRequestController::class, 'archived'])
                ->name('requests.archived');
            Route::get('/requests/archived/{supplyRequest}', [RequestorRequestController::class, 'showArchived'])
                ->name('requests.archived.show');
            Route::get('/requests/{supplyRequest}', [RequestorRequestController::class, 'show'])
                ->name('requests.show');
            Route::patch('/requests/{supplyRequest}/cancel', [RequestorRequestController::class, 'cancel'])
                ->name('requests.cancel');
            Route::patch('/requests/{supplyRequest}/reopen', [RequestorRequestController::class, 'reopen'])
                ->name('requests.reopen');
        });

    // ─── Approver ───────────────────────────────────────────────
    Route::middleware('role:approver')
        ->prefix('approver')
        ->name('approver.')
        ->group(function () {
            Route::get('/approvals', [ApproverRequestController::class, 'index'])->name('approvals.index');
            Route::get('/approvals/{supplyRequest}', [ApproverRequestController::class, 'show'])->name('approvals.show');
            Route::patch('/approvals/{supplyRequest}/approve', [ApproverRequestController::class, 'approve'])->name('approvals.approve');
            Route::patch('/approvals/{supplyRequest}/reject', [ApproverRequestController::class, 'reject'])->name('approvals.reject');
            Route::patch('/approvals/{supplyRequest}/items/{item}', [ApproverRequestController::class, 'updateItem'])->name('approvals.items.update');
            Route::delete('/approvals/{supplyRequest}/items/{item}', [ApproverRequestController::class, 'removeItem'])->name('approvals.items.destroy');
        });
});

require __DIR__ . '/auth.php';
