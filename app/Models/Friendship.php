<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
        'action_user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations

    /**
     * যে user request পাঠিয়েছে
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * যে user request receive করেছে
     */
    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * যে user action নিয়েছে (accept/reject)
     */
    public function actionUser()
    {
        return $this->belongsTo(User::class, 'action_user_id');
    }

    // Scopes

    /**
     * Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Accepted friendships
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Blocked users
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    // Boot method to update counter cache
    protected static function boot()
    {
        parent::boot();

        // যখন friendship accept হয়
        static::updated(function ($friendship) {
            if ($friendship->status === 'accepted' && $friendship->getOriginal('status') !== 'accepted') {
                $friendship->user()->increment('friends_count');
                $friendship->friend()->increment('friends_count');
            }
        });

        // যখন friendship delete হয়
        static::deleted(function ($friendship) {
            if ($friendship->status === 'accepted') {
                $friendship->user()->decrement('friends_count');
                $friendship->friend()->decrement('friends_count');
            }
        });
    }

    // Helper Methods

    /**
     * Accept friend request
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'action_user_id' => auth()->id(),
        ]);
    }

    /**
     * Reject friend request
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'action_user_id' => auth()->id(),
        ]);
    }

    /**
     * Block user
     */
    public function block()
    {
        $this->update([
            'status' => 'blocked',
            'action_user_id' => auth()->id(),
        ]);
    }
}