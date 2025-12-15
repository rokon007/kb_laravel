<?php

namespace App\Traits;

use App\Models\Like;
use App\Models\User;

trait Likeable
{
    /**
     * Get all likes
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Check if user has liked
     */
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    /**
     * Like by user
     */
    public function likeBy(User $user)
    {
        if (!$this->isLikedBy($user)) {
            return $this->likes()->create([
                'user_id' => $user->id,
            ]);
        }

        return false;
    }

    /**
     * Unlike by user
     */
    public function unlikeBy(User $user)
    {
        return $this->likes()->where('user_id', $user->id)->delete();
    }

    /**
     * Toggle like
     */
    public function toggleLike(User $user)
    {
        if ($this->isLikedBy($user)) {
            return $this->unlikeBy($user);
        }

        return $this->likeBy($user);
    }

    /**
     * Get likes count
     */
    public function getLikesCount(): int
    {
        return $this->likes_count ?? 0;
    }
}