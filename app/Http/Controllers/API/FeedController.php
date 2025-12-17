<?php

namespace App\Http\Controllers/API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\FeedService;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    protected $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    /**
     * Get user's personalized feed
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $page = $request->get('page', 1);

        // Get posts from friends and following
        $friendIds = $user->friends()->pluck('id');
        $followingIds = $user->following()
            ->where('followable_type', \App\Models\User::class)
            ->pluck('followable_id');

        $userIds = $friendIds->merge($followingIds)->unique();

        $posts = Post::with(['user', 'page'])
            ->whereIn('user_id', $userIds)
            ->orWhere('user_id', $user->id)
            ->where('privacy', 'public')
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        // Add ads
        $postsWithAds = $this->feedService->insertAds($posts->items(), $page);

        return response()->json([
            'success' => true,
            'data' => $postsWithAds,
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Get reels feed
     */
    public function reels(Request $request)
    {
        $page = $request->get('page', 1);

        $reels = Post::with(['user'])
            ->where('type', 'reel')
            ->where('privacy', 'public')
            ->where('status', 'active')
            ->inRandomOrder()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reels,
        ]);
    }

    /**
     * Get trending posts
     */
    public function trending()
    {
        $posts = Post::with(['user', 'page'])
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'active')
            ->orderByRaw('(likes_count * 2 + comments_count * 3 + shares_count * 5) DESC')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }
}