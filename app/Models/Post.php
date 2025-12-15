<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'page_id',
        'content',
        'type',
        'media_url',
        'thumbnail_url',
        'video_duration',
        'privacy',
        'is_boosted',
        'boost_budget',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'status',
    ];

    protected $casts = [
        'video_duration' => 'integer',
        'is_boosted' => 'boolean',
        'boost_budget' => 'decimal:2',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'views_count' => 'integer',
    ];

    protected $with = ['user']; // Always load user

    // Relations

    /**
     * Post এর author
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Post যদি কোনো page থেকে হয়
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Post এর comments
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Post এর top level comments (no parent)
     */
    public function topLevelComments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * Post এর likes
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Post এর hashtags
     */
    public function hashtags()
    {
        return $this->hasMany(PostHashtag::class);
    }

    /**
     * Post এর reports
     */
    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Post থেকে creator earnings
     */
    public function earnings()
    {
        return $this->hasMany(CreatorEarning::class);
    }

    // Scopes

    /**
     * Only public posts
     */
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    /**
     * Only active posts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Posts by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Videos and reels
     */
    public function scopeVideoContent($query)
    {
        return $query->whereIn('type', ['video', 'reel']);
    }

    /**
     * Posts from user's friends
     */
    public function scopeFromFriends($query, User $user)
    {
        $friendIds = $user->friends()->pluck('friend_id');
        
        return $query->whereIn('user_id', $friendIds);
    }

    // Helper Methods

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Check if user has liked this post
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Get media URL with full path
     */
    public function getFullMediaUrlAttribute(): ?string
    {
        if (!$this->media_url) {
            return null;
        }

        return config('app.storage_url') . '/' . $this->media_url;
    }

    /**
     * Get thumbnail URL with full path
     */
    public function getFullThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_url) {
            return null;
        }

        return config('app.storage_url') . '/' . $this->thumbnail_url;
    }

    /**
     * Check if post is video or reel
     */
    public function isVideoContent(): bool
    {
        return in_array($this->type, ['video', 'reel']);
    }
}