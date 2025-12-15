<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'category',
        'avatar',
        'cover_photo',
        'is_verified',
        'followers_count',
        'is_active',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'followers_count' => 'integer',
    ];

    // Relations

    /**
     * Page এর owner
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Page এর posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Page এর followers (polymorphic)
     */
    public function followers()
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    // Scopes

    /**
     * Only active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Only verified pages
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Pages by category
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Helper Methods

    /**
     * Check if user is following this page
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()
            ->where('follower_id', $user->id)
            ->exists();
    }

    /**
     * Check if user owns this page
     */
    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}