<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostHashtag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'hashtag',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * Hashtag এর post
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Scopes

    /**
     * Posts with specific hashtag
     */
    public function scopeWithHashtag($query, string $hashtag)
    {
        return $query->where('hashtag', $hashtag);
    }

    /**
     * Trending hashtags
     */
    public function scopeTrending($query, int $days = 7)
    {
        return $query->select('hashtag', \DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('hashtag')
            ->orderByDesc('count')
            ->limit(20);
    }

    // Helper Methods

    /**
     * Get trending hashtags
     */
    public static function getTrending(int $limit = 10): array
    {
        return self::select('hashtag', \DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('hashtag')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'hashtag')
            ->toArray();
    }
}