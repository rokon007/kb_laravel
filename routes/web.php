<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;

    Route::get('/', function () {
        return view('welcome');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        // Admin login routes (guest only)
        Route::middleware('guest')->group(function () {
            Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login']);
        });
    });

    Route::prefix('admin')->name('filament.admin.auth.')->group(function () {
        // Admin logout (authenticated only)
        Route::middleware('auth')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        });

        Route::get('/admin-login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    });


