<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RentalController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TenantVerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\RentalController as TenantRentalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('tenant')->name('tenant.')->middleware('approved.tenant')->group(function () {
        Route::get('/rentals', [TenantRentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/new', [TenantRentalController::class, 'create'])->name('rentals.create');
        Route::get('/garages/available', [TenantRentalController::class, 'available'])->name('garages.available');
        Route::post('/rentals/quote', [TenantRentalController::class, 'quote'])->name('rentals.quote');
        Route::post('/rentals', [TenantRentalController::class, 'store'])->name('rentals.store');
        Route::get('/payments/{payment}/receipt', [TenantRentalController::class, 'receipt'])->name('payments.receipt');
    });

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/{rental}/extend', [RentalController::class, 'edit'])->name('rentals.edit');
        Route::post('/rentals/{rental}/extend', [RentalController::class, 'update'])->name('rentals.extend');
        Route::get('/payments/{payment}/receipt', [RentalController::class, 'receipt'])->name('payments.receipt');
        Route::get('/garasi', [GarageController::class, 'index'])->name('garages.index');
        Route::post('/garasi', [GarageController::class, 'store'])->name('garages.store');
        Route::get('/penyewa', [TenantVerificationController::class, 'index'])->name('tenants.index');
        Route::patch('/penyewa/{tenant}/verifikasi', [TenantVerificationController::class, 'update'])
            ->name('tenants.verify');
    });
});
