<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * Like করা user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Like করা item (Post or Comment)
     */
    public function likeable()
    {
        return $this->morphTo();
    }

    // Boot method to update counter cache
    protected static function boot()
    {
        parent::boot();

        // যখন like create হয়
        static::created(function ($like) {
            $like->likeable()->increment('likes_count');
        });

        // যখন like delete হয়
        static::deleted(function ($like) {
            $like->likeable()->decrement('likes_count');
        });
    }
}