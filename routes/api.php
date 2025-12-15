<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SocialAuthController;

// Public routes
Route::prefix('auth')->group(function () {
    // Registration
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistrationOtp']);
    
    // Login
    Route::post('/login', [AuthController::class, 'login']);
    
    // OTP
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    
    // Social Login
    Route::get('/social/{provider}', [SocialAuthController::class, 'redirectToProvider']);
    Route::get('/social/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});