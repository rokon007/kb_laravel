<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Friendship;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    /**
     * Send friend request
     */
    public function sendRequest(Request $request)
    {
        $request->validate([
            'friend_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user()->id;
        $friendId = $request->friend_id;

        // Can't send request to self
        if ($userId === $friendId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send friend request to yourself',
            ], 422);
        }

        // Check if already friends or request exists
        $existing = Friendship::where(function($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Friend request already sent or you are already friends',
            ], 422);
        }

        $friendship = Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 'pending',
            'action_user_id' => $userId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully',
            'data' => $friendship,
        ], 201);
    }

    /**
     * Get friend requests
     */
    public function requests(Request $request)
    {
        $requests = $request->user()
            ->receivedFriendRequests()
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Accept friend request
     */
    public function acceptRequest(Friendship $friendship)
    {
        // Check if request is for current user
        if ($friendship->friend_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $friendship->accept();

        return response()->json([
            'success' => true,
            'message' => 'Friend request accepted',
            'data' => $friendship->fresh(),
        ]);
    }

    /**
     * Reject friend request
     */
    public function rejectRequest(Friendship $friendship)
    {
        // Check if request is for current user
        if ($friendship->friend_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $friendship->reject();

        return response()->json([
            'success' => true,
            'message' => 'Friend request rejected',
        ]);
    }

    /**
     * Cancel friend request
     */
    public function cancelRequest(Friendship $friendship)
    {
        // Check if request is from current user
        if ($friendship->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Friend request cancelled',
        ]);
    }

    /**
     * Get friends list
     */
    public function friends(Request $request)
    {
        $friends = $request->user()
            ->friends()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $friends,
        ]);
    }

    /**
     * Unfriend
     */
    public function unfriend(Request $request, User $user)
    {
        $userId = $request->user()->id;
        $friendId = $user->id;

        $friendship = Friendship::where(function($q) use ($userId, $friendId) {
            $q->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function($q) use ($userId, $friendId) {
            $q->where('user_id', $friendId)->where('friend_id', $userId);
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json([
                'success' => false,
                'message' => 'Not friends with this user',
            ], 404);
        }

        $friendship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unfriended successfully',
        ]);
    }
}