<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redirect to social provider
     */
    public function redirectToProvider(string $provider)
    {
        try {
            return $this->socialAuthService->redirectToProvider($provider);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Handle provider callback
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $user = $this->socialAuthService->handleProviderCallback($provider);

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            // For web, you might want to redirect to frontend with token
            // For API, return JSON response
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}