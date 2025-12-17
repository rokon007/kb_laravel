<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Get user's pages
     */
    public function index(Request $request)
    {
        $pages = $request->user()
            ->pages()
            ->withCount('followers')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Create new page
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
        ]);

        $page = Page::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(6),
            'description' => $request->description,
            'category' => $request->category,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    /**
     * Get single page
     */
    public function show($identifier)
    {
        $page = Page::where('id', $identifier)
            ->orWhere('slug', $identifier)
            ->firstOrFail();

        $page->load('user');
        $page->loadCount('followers');

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    /**
     * Update page
     */
    public function update(Request $request, Page $page)
    {
        // Check ownership
        if (!$page->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
        ]);

        $page->update($request->only(['name', 'description', 'category']));

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page,
        ]);
    }

    /**
     * Delete page
     */
    public function destroy(Request $request, Page $page)
    {
        // Check ownership
        if (!$page->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }

    /**
     * Get page posts
     */
    public function posts(Page $page)
    {
        $posts = $page->posts()
            ->with(['user'])
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Follow/Unfollow page
     */
    public function toggleFollow(Request $request, Page $page)
    {
        $user = $request->user();

        if ($page->isFollowedBy($user)) {
            $page->followers()->where('follower_id', $user->id)->delete();
            $message = 'Page unfollowed';
        } else {
            $page->followers()->create(['follower_id' => $user->id]);
            $message = 'Page followed';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'followers_count' => $page->fresh()->followers_count,
                'is_following' => $page->fresh()->isFollowedBy($user),
            ],
        ]);
    }
}