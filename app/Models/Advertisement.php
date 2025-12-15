<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'media_type',
        'media_url',
        'click_url',
        'placement_type',
        'target_age_min',
        'target_age_max',
        'target_gender',
        'target_locations',
        'target_interests',
        'budget',
        'spent',
        'impressions',
        'clicks',
        'status',
        'admin_note',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'target_age_min' => 'integer',
        'target_age_max' => 'integer',
        'target_locations' => 'array',
        'target_interests' => 'array',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relations

    /**
     * Advertisement এর advertiser (user)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Advertisement এর impressions
     */
    public function adImpressions()
    {
        return $this->hasMany(AdImpression::class);
    }

    /**
     * Advertisement এর clicks
     */
    public function adClicks()
    {
        return $this->hasMany(AdClick::class);
    }

    /**
     * Advertisement থেকে creator earnings
     */
    public function earnings()
    {
        return $this->hasMany(CreatorEarning::class);
    }

    // Scopes

    /**
     * Running ads
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running')
            ->where('budget', '>', \DB::raw('spent'))
            ->where(function ($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Pending approval ads
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Ads by placement type
     */
    public function scopeForPlacement($query, string $type)
    {
        return $query->where('placement_type', $type);
    }

    // Helper Methods

    /**
     * Calculate CTR (Click Through Rate)
     */
    public function getCtrAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return ($this->clicks / $this->impressions) * 100;
    }

    /**
     * Calculate CPC (Cost Per Click)
     */
    public function getCpcAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return $this->spent / $this->clicks;
    }

    /**
     * Calculate CPM (Cost Per Mille - 1000 impressions)
     */
    public function getCpmAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return ($this->spent / $this->impressions) * 1000;
    }

    /**
     * Check if ad budget is exhausted
     */
    public function isBudgetExhausted(): bool
    {
        return $this->spent >= $this->budget;
    }

    /**
     * Check if ad is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Remaining budget
     */
    public function getRemainingBudgetAttribute(): float
    {
        return max(0, $this->budget - $this->spent);
    }
}