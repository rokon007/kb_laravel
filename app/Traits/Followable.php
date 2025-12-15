<?php

namespace App\Traits;

use App\Models\Follow;
use App\Models\User;

trait Followable
{
    /**
     * Get all followers
     */
    public function followers()
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    /**
     * Check if user is following
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    /**
     * Get followers count
     */
    public function getFollowersCount(): int
    {
        return $this->followers_count ?? 0;
    }
}