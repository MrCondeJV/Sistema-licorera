<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessRegistrationController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/unlock', [AuthController::class, 'showUnlock'])->name('unlock');
Route::post('/unlock', [AuthController::class, 'unlock'])->name('unlock.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public SaaS Tenant Registration (Deshabilitado para Producción — Alta gestionada por Superadmin)
Route::redirect('/register-business', '/login')->name('business.register');

/*
|--------------------------------------------------------------------------
| Authenticated POS Operations
|--------------------------------------------------------------------------
*/
Route::middleware(['pin.auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('pos.index');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tenant/switch/{tenant}', [AuthController::class, 'switchTenant'])->name('tenant.switch');

    // POS Sales screen (Counter quick sales)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/quick-customer', [PosController::class, 'quickCustomer'])->name('pos.customer.quick');
    Route::get('/pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');

    // Tables and Open Tabs
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::get('/tables/{table}', [TableController::class, 'show'])->name('tables.show');
    Route::post('/tables/open', [TableController::class, 'open'])->name('tables.open');
    Route::post('/tables/{table}/add-item', [TableController::class, 'addItem'])->name('tables.add-item');
    Route::post('/tables/{table}/remove-item/{itemId}', [TableController::class, 'removeItem'])->name('tables.remove-item');
    Route::post('/tables/{table}/transfer', [TableController::class, 'transfer'])->name('tables.transfer');
    Route::post('/tables/{table}/cancel', [TableController::class, 'cancel'])->name('tables.cancel');

    // Cash Register & Shifts
    Route::get('/cash', [CashRegisterController::class, 'index'])->name('cash.index');
    Route::post('/cash/open', [CashRegisterController::class, 'open'])->name('cash.open');
    Route::post('/cash/{session}/close', [CashRegisterController::class, 'close'])->name('cash.close');
    Route::post('/cash/{session}/movement', [CashRegisterController::class, 'movement'])->name('cash.movement');
    Route::get('/cash/history', [CashRegisterController::class, 'history'])->name('cash.history');

    // Offline Sync endpoint
    Route::post('/sync/offline', [OfflineSyncController::class, 'sync'])->name('sync.offline');

    // Inventory Module
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/kardex', [InventoryController::class, 'kardex'])->name('inventory.kardex');
    Route::get('/inventory/counts', [InventoryController::class, 'counts'])->name('inventory.counts');
    Route::post('/inventory/counts', [InventoryController::class, 'storeCount'])->name('inventory.counts.store');
    Route::post('/inventory/counts/{count}/apply', [InventoryController::class, 'applyCount'])->name('inventory.counts.apply');

    // Admin-only or Elevated Modules
    Route::middleware(['role:admin,cashier'])->group(function () {
        Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/inventory/{product}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::post('/inventory/{product}/adjust', [InventoryController::class, 'quickAdjust'])->name('inventory.adjust');

        // Purchases & Suppliers
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');

        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::post('/suppliers/quick', [SupplierController::class, 'quickStore'])->name('suppliers.quick');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::post('/suppliers/{supplier}/toggle', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle');

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

        // Customers & Debts (Fiados / Abonos)
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/debts', [CustomerController::class, 'debts'])->name('customers.debts');
        Route::post('/customers/{customer}/debts', [CustomerController::class, 'storeDebt'])->name('customers.debts.store');
        Route::post('/customers/{customer}/payments', [CustomerController::class, 'storePayment'])->name('customers.payments.store');

        // Promotions
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::post('/promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');

        // Reports & Exports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
        Route::get('/reports/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');
    });

    // Admin Only
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

/*
|--------------------------------------------------------------------------
| Superadmin Platform Area
|--------------------------------------------------------------------------
*/
Route::middleware(['pin.auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperadminController::class, 'index'])->name('index');
    Route::get('/tenants/create', [SuperadminController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [SuperadminController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [SuperadminController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [SuperadminController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/enter', [SuperadminController::class, 'enter'])->name('tenants.enter');
    Route::get('/leave', [SuperadminController::class, 'leave'])->name('leave');
    Route::get('/audit', [SuperadminController::class, 'audit'])->name('audit');
    Route::post('/tenants/{tenant}/toggle', [SuperadminController::class, 'toggleTenantStatus'])->name('tenants.toggle');
    Route::post('/tenants/{tenant}/plan', [SuperadminController::class, 'updatePlan'])->name('tenants.plan');
});
