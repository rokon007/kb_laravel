<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'content',
        'likes_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    protected $with = ['user']; // Always load user

    // Relations

    /**
     * Comment এর post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Comment এর author
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent comment (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Comment এর replies
     */
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Comment এর likes
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // Scopes

    /**
     * Only top level comments
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Only replies
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Helper Methods

    /**
     * Check if this is a reply
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if user has liked this comment
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}