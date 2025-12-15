<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'follower_id',
        'followable_id',
        'followable_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * যে user follow করছে
     */
    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * যাকে follow করা হয়েছে (User or Page)
     */
    public function followable()
    {
        return $this->morphTo();
    }

    // Boot method to update counter cache
    protected static function boot()
    {
        parent::boot();

        // যখন follow create হয়
        static::created(function ($follow) {
            $follow->followable()->increment('followers_count');
            
            if ($follow->followable_type === User::class) {
                $follow->follower()->increment('following_count');
            }
        });

        // যখন unfollow হয়
        static::deleted(function ($follow) {
            $follow->followable()->decrement('followers_count');
            
            if ($follow->followable_type === User::class) {
                $follow->follower()->decrement('following_count');
            }
        });
    }
}