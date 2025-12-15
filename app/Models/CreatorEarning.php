<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreatorEarning extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'advertisement_id',
        'type',
        'amount',
        'admin_share',
        'creator_share',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'admin_share' => 'decimal:4',
        'creator_share' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * Earning এর creator
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Earning যে post থেকে
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Earning যে ad থেকে
     */
    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    // Scopes

    /**
     * Earnings by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * View earnings
     */
    public function scopeFromViews($query)
    {
        return $query->where('type', 'view');
    }

    /**
     * Ad impression earnings
     */
    public function scopeFromImpressions($query)
    {
        return $query->where('type', 'impression');
    }

    /**
     * Earnings in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Today's earnings
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * This month's earnings
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}