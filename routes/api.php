<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SocialAuthController;

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\FriendshipController;
use App\Http\Controllers\API\FollowController;
use App\Http\Controllers\API\FeedController;
use App\Http\Controllers\API\AdvertisementController;
use App\Http\Controllers\API\MonetizationController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\NotificationController;

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




// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    // Route::prefix('auth')->group(function () {
    //     Route::post('/logout', [AuthController::class, 'logout']);
    //     Route::get('/me', [AuthController::class, 'me']);
    // });
    
    // User
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::get('/search', [UserController::class, 'search']);
    });
    
    Route::get('/users/{identifier}', [UserController::class, 'show']);
    Route::get('/users/{identifier}/posts', [UserController::class, 'posts']);
    
    // Posts
    Route::apiResource('posts', PostController::class);
    Route::post('/posts/{post}/like', [PostController::class, 'toggleLike']);
    Route::post('/posts/{post}/share', [PostController::class, 'share']);
    Route::get('/posts/{post}/comments', [PostController::class, 'comments']);
    Route::post('/posts/{post}/comments', [PostController::class, 'addComment']);
    
    // Pages
    Route::apiResource('pages', PageController::class);
    Route::get('/pages/{page}/posts', [PageController::class, 'posts']);
    Route::post('/pages/{page}/follow', [PageController::class, 'toggleFollow']);
    
    // Friendships
    Route::prefix('friends')->group(function () {
        Route::post('/request', [FriendshipController::class, 'sendRequest']);
        Route::get('/requests', [FriendshipController::class, 'requests']);
        Route::put('/requests/{friendship}/accept', [FriendshipController::class, 'acceptRequest']);
        Route::put('/requests/{friendship}/reject', [FriendshipController::class, 'rejectRequest']);
        Route::delete('/requests/{friendship}/cancel', [FriendshipController::class, 'cancelRequest']);
        Route::get('/', [FriendshipController::class, 'friends']);
        Route::delete('/{user}', [FriendshipController::class, 'unfriend']);
    });
    
    // Follow
    Route::prefix('follow')->group(function () {
        Route::post('/users/{user}', [FollowController::class, 'followUser']);
        Route::delete('/users/{user}', [FollowController::class, 'unfollowUser']);
        Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
        Route::get('/users/{user}/following', [FollowController::class, 'following']);
    });
    
    // Feed
    Route::prefix('feed')->group(function () {
        Route::get('/', [FeedController::class, 'index']);
        Route::get('/reels', [FeedController::class, 'reels']);
        Route::get('/trending', [FeedController::class, 'trending']);
    });
    
    // Advertisements
    Route::apiResource('advertisements', AdvertisementController::class);
    Route::post('/advertisements/{advertisement}/impression', [AdvertisementController::class, 'recordImpression']);
    Route::post('/advertisements/{advertisement}/click', [AdvertisementController::class, 'recordClick']);
    Route::get('/advertisements/{advertisement}/analytics', [AdvertisementController::class, 'analytics']);
    
    // Monetization
    Route::prefix('monetization')->group(function () {
        Route::get('/eligibility', [MonetizationController::class, 'checkEligibility']);
        Route::post('/apply', [MonetizationController::class, 'apply']);
        Route::get('/earnings', [MonetizationController::class, 'earnings']);
        Route::get('/summary', [MonetizationController::class, 'summary']);
    });
    
    // Wallet
    Route::prefix('wallet')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::get('/transactions', [WalletController::class, 'transactions']);
        Route::post('/withdraw', [WalletController::class, 'requestWithdrawal']);
        Route::get('/withdrawals', [WalletController::class, 'withdrawalRequests']);
    });
    
    // Payment
    Route::prefix('payment')->group(function () {
        Route::post('/bkash/initiate', [PaymentController::class, 'initiateBkash']);
        Route::post('/nagad/initiate', [PaymentController::class, 'initiateNagad']);
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });
});

// Payment Callbacks (Public)
Route::get('/payment/bkash/callback', [PaymentController::class, 'bkashCallback']);
Route::get('/payment/nagad/callback', [PaymentController::class, 'nagadCallback']);