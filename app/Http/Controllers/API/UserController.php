<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['pages', 'wallet', 'advertiserWallet']),
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:500',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'location' => 'sometimes|string|max:255',
        ]);

        $user = $request->user();
        $user->update($request->only([
            'name',
            'bio',
            'date_of_birth',
            'gender',
            'location',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Get user by ID or username
     */
    public function show($identifier)
    {
        $user = User::where('id', $identifier)
            ->orWhere('username', $identifier)
            ->firstOrFail();

        $user->load(['pages']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Get user's posts
     */
    public function posts($identifier)
    {
        $user = User::where('id', $identifier)
            ->orWhere('username', $identifier)
            ->firstOrFail();

        $posts = $user->posts()
            ->with(['user', 'page'])
            ->where('privacy', 'public')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Search users
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $users = User::where('name', 'like', "%{$request->q}%")
            ->orWhere('username', 'like', "%{$request->q}%")
            ->where('is_banned', false)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}