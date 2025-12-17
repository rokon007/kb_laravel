<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Jobs\ProcessVideoUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Get all posts (paginated)
     */
    public function index(Request $request)
    {
        $posts = Post::with(['user', 'page'])
            ->where('privacy', 'public')
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Create new post
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string|max:5000',
            'type' => 'required|in:text,image,video,reel',
            'privacy' => 'required|in:public,friends,private',
            'page_id' => 'nullable|exists:pages,id',
            'media' => 'nullable|file|max:102400', // 100MB
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'page_id' => $request->page_id,
            'content' => $request->content,
            'type' => $request->type,
            'privacy' => $request->privacy,
            'status' => 'active',
        ]);

        // Handle media upload
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            
            if (in_array($post->type, ['video', 'reel'])) {
                // Store temporarily and process via queue
                $path = $file->store('temp');
                ProcessVideoUpload::dispatch($post, $path);
                
                $message = 'Post created. Video is being processed.';
            } else {
                // Handle image upload
                $path = $file->store("images/{$post->user_id}");
                $post->update(['media_url' => $path]);
                
                $message = 'Post created successfully';
            }
        } else {
            $message = 'Post created successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $post->load('user'),
        ], 201);
    }

    /**
     * Get single post
     */
    public function show(Post $post)
    {
        // Check privacy
        if ($post->privacy === 'private' && $post->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found',
            ], 404);
        }

        $post->load(['user', 'page', 'comments.user']);
        
        // Increment views
        $post->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    /**
     * Update post
     */
    public function update(Request $request, Post $post)
    {
        // Check ownership
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'content' => 'nullable|string|max:5000',
            'privacy' => 'sometimes|in:public,friends,private',
        ]);

        $post->update($request->only(['content', 'privacy']));

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post,
        ]);
    }

    /**
     * Delete post
     */
    public function destroy(Request $request, Post $post)
    {
        // Check ownership
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete media files
        if ($post->media_url) {
            Storage::delete($post->media_url);
        }
        if ($post->thumbnail_url) {
            Storage::delete($post->thumbnail_url);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ]);
    }

    /**
     * Like/Unlike post
     */
    public function toggleLike(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->isLikedBy($user)) {
            $post->likes()->where('user_id', $user->id)->delete();
            $message = 'Post unliked';
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $message = 'Post liked';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'likes_count' => $post->fresh()->likes_count,
                'is_liked' => $post->fresh()->isLikedBy($user),
            ],
        ]);
    }

    /**
     * Get post comments
     */
    public function comments(Post $post)
    {
        $comments = $post->topLevelComments()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    /**
     * Add comment to post
     */
    public function addComment(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

    /**
     * Share post
     */
    public function share(Request $request, Post $post)
    {
        $post->increment('shares_count');

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully',
            'data' => [
                'shares_count' => $post->shares_count,
            ],
        ]);
    }
}