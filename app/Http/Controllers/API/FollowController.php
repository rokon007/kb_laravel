<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /**
     * Follow user
     */
    public function followUser(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot follow yourself',
            ], 422);
        }

        if ($request->user()->isFollowing($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Already following this user',
            ], 422);
        }

        $request->user()->following()->create([
            'followable_id' => $user->id,
            'followable_type' => User::class,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User followed successfully',
            'data' => [
                'followers_count' => $user->fresh()->followers_count,
            ],
        ]);
    }

    /**
     * Unfollow user
     */
    public function unfollowUser(Request $request, User $user)
    {
        $request->user()->following()
            ->where('followable_id', $user->id)
            ->where('followable_type', User::class)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'User unfollowed successfully',
            'data' => [
                'followers_count' => $user->fresh()->followers_count,
            ],
        ]);
    }

    /**
     * Get user's followers
     */
    public function followers(User $user)
    {
        $followers = $user->followers()
            ->with('follower')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $followers,
        ]);
    }

    /**
     * Get user's following
     */
    public function following(User $user)
    {
        $following = $user->following()
            ->with('followable')
            ->where('followable_type', User::class)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $following,
        ]);
    }
}