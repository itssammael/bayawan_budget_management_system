<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AipController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [BudgetController::class, 'dashboard'])->name('dashboard');

    // Budget Management
    Route::get('/budget/appropriations', [BudgetController::class, 'index'])->name('budget.appropriations');
    Route::post('/budget/appropriations', [BudgetController::class, 'store'])->name('budget.appropriations.store');
    Route::put('/budget/appropriations/{appropriation}', [BudgetController::class, 'update'])->name('budget.appropriations.update');
    Route::delete('/budget/appropriations/{appropriation}', [BudgetController::class, 'destroy'])->name('budget.appropriations.destroy');
    
    // Procurement Tracker
    Route::get('/budget/procurement', [ProcurementController::class, 'index'])->name('budget.procurement');
    Route::post('/budget/procurement', [ProcurementController::class, 'store'])->name('budget.procurement.store');
    Route::post('/budget/procurement/bulk', [ProcurementController::class, 'bulkStore'])->name('budget.procurement.bulk-store');
    Route::put('/budget/procurement/{procurement}', [ProcurementController::class, 'update'])->name('budget.procurement.update');
    Route::delete('/budget/procurement/{procurement}', [ProcurementController::class, 'destroy'])->name('budget.procurement.destroy');

    // Admin Only Sections (Settings, Users, Roles, Departments)
    // User Management (List view for all auth users)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Admin Only Sections (Settings, Roles, Departments)
    Route::middleware(['admin'])->group(function () {
        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/fund-sources', [SettingsController::class, 'storeFundSource'])->name('settings.fund-sources.store');
        Route::put('/settings/fund-sources/{fundSource}', [SettingsController::class, 'updateFundSource'])->name('settings.fund-sources.update');
        Route::delete('/settings/fund-sources/{fundSource}', [SettingsController::class, 'destroyFundSource'])->name('settings.fund-sources.destroy');

        Route::post('/settings/budget-years', [SettingsController::class, 'storeBudgetYear'])->name('settings.budget-years.store');
        Route::put('/settings/budget-years/{budgetYear}', [SettingsController::class, 'updateBudgetYear'])->name('settings.budget-years.update');
        Route::put('/settings/budget-years/{budgetYear}/set-current', [SettingsController::class, 'setCurrentBudgetYear'])->name('settings.budget-years.set-current');

        Route::post('/settings/ppsas', [SettingsController::class, 'storePpsa'])->name('settings.ppsas.store');
        Route::put('/settings/ppsas/{ppsa}', [SettingsController::class, 'updatePpsa'])->name('settings.ppsas.update');
        Route::delete('/settings/ppsas/{ppsa}', [SettingsController::class, 'destroyPpsa'])->name('settings.ppsas.destroy');

        Route::post('/settings/budget-classifications', [SettingsController::class, 'storeBudgetClassification'])->name('settings.budget-classifications.store');
        Route::put('/settings/budget-classifications/{budgetClassification}', [SettingsController::class, 'updateBudgetClassification'])->name('settings.budget-classifications.update');
        Route::delete('/settings/budget-classifications/{budgetClassification}', [SettingsController::class, 'destroyBudgetClassification'])->name('settings.budget-classifications.destroy');

        Route::post('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');

        // Role Management
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Department Management
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });

    // User Management Actions (Store, Update, Destroy) for users with 'admin' role
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // AIP Management
    Route::get('/budget/aip', [AipController::class, 'index'])->name('budget.aip');
    Route::post('/budget/aip', [AipController::class, 'store'])->name('budget.aip.store');
    Route::post('/budget/aip/bulk', [AipController::class, 'bulkStore'])->name('budget.aip.bulk-store');
    Route::put('/budget/aip/{aip}', [AipController::class, 'update'])->name('budget.aip.update');
    Route::delete('/budget/aip/{aip}', [AipController::class, 'destroy'])->name('budget.aip.destroy');
});
