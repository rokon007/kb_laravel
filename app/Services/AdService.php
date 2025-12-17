<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\User;
use App\Models\Post;
use App\Models\AdImpression;
use Illuminate\Support\Facades\Cache;

class AdService
{
    /**
     * Get ad for feed placement
     * প্রতি X পোস্ট পরে ১টি ad দেখাবে
     */
    public function getFeedAd(User $user = null, int $postIndex = 0): ?Advertisement
    {
        // Get feed ad frequency from settings (e.g., every 5 posts)
        $frequency = $this->getSetting('feed_ad_frequency', 5);
        
        // Check if this position should show an ad
        if ($postIndex % $frequency !== 0) {
            return null;
        }
        
        return $this->getTargetedAd('feed', $user);
    }
    
    /**
     * Get ad for reel placement
     */
    public function getReelAd(User $user = null, int $reelIndex = 0): ?Advertisement
    {
        $frequency = $this->getSetting('reel_ad_frequency', 3);
        
        if ($reelIndex % $frequency !== 0) {
            return null;
        }
        
        return $this->getTargetedAd('reel', $user);
    }
    
    /**
     * Get pre-roll ad for video
     */
    public function getVideoPrerollAd(User $user = null): ?Advertisement
    {
        return $this->getTargetedAd('video_preroll', $user);
    }
    
    /**
     * Get mid-roll ad for video (at specific timestamp)
     */
    public function getVideoMidrollAd(User $user = null, int $videoDuration = 0): ?Advertisement
    {
        // Only show mid-roll for videos longer than 5 minutes
        if ($videoDuration < 300) {
            return null;
        }
        
        return $this->getTargetedAd('video_midroll', $user);
    }
    
    /**
     * Get targeted advertisement based on user profile
     */
    protected function getTargetedAd(string $placementType, ?User $user): ?Advertisement
    {
        $query = Advertisement::where('status', 'running')
            ->where('placement_type', $placementType)
            ->where('budget', '>', \DB::raw('spent'))
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
        
        // Apply targeting if user is logged in
        if ($user) {
            $query = $this->applyUserTargeting($query, $user);
        }
        
        // Get random ad (weighted by remaining budget)
        $ads = $query->get();
        
        if ($ads->isEmpty()) {
            return null;
        }
        
        // Weight by remaining budget (ads with more budget have higher chance)
        $selectedAd = $ads->random();
        
        // Cache to prevent same ad showing multiple times in short period
        $cacheKey = "user_{$user?->id}_last_ad_{$placementType}";
        $lastAdId = Cache::get($cacheKey);
        
        // If same ad, try to get different one
        if ($lastAdId === $selectedAd->id && $ads->count() > 1) {
            $selectedAd = $ads->where('id', '!=', $lastAdId)->random();
        }
        
        Cache::put($cacheKey, $selectedAd->id, now()->addMinutes(30));
        
        return $selectedAd;
    }
    
    /**
     * Apply user targeting filters
     */
    protected function applyUserTargeting($query, User $user)
    {
        // Age targeting
        if ($user->date_of_birth) {
            $userAge = $user->date_of_birth->age;
            
            $query->where(function($q) use ($userAge) {
                $q->whereNull('target_age_min')
                  ->orWhere('target_age_min', '<=', $userAge);
            })->where(function($q) use ($userAge) {
                $q->whereNull('target_age_max')
                  ->orWhere('target_age_max', '>=', $userAge);
            });
        }
        
        // Gender targeting
        if ($user->gender) {
            $query->where(function($q) use ($user) {
                $q->where('target_gender', 'all')
                  ->orWhere('target_gender', $user->gender);
            });
        }
        
        // Location targeting
        if ($user->location) {
            $query->where(function($q) use ($user) {
                $q->whereNull('target_locations')
                  ->orWhereJsonContains('target_locations', $user->location);
            });
        }
        
        // Interest targeting (based on user's followed pages/hashtags)
        // ... implement interest matching logic
        
        return $query;
    }
    
    /**
     * Record ad impression
     */
    public function recordImpression(Advertisement $ad, User $user = null, string $ipAddress = null)
    {
        // Create impression record
        AdImpression::create([
            'advertisement_id' => $ad->id,
            'user_id' => $user?->id,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        // Increment ad impressions counter
        $ad->increment('impressions');
        
        // Calculate cost per impression (CPM model)
        $costPerImpression = 0.10; // 0.10 BDT per impression
        
        // Deduct from advertiser budget
        $ad->increment('spent', $costPerImpression);
        
        // If budget exhausted, pause the ad
        if ($ad->spent >= $ad->budget) {
            $ad->update(['status' => 'completed']);
        }
        
        // Record transaction for advertiser
        Transaction::create([
            'user_id' => $ad->user_id,
            'wallet_id' => $ad->user->advertiserWallet->id,
            'type' => 'ad_spend',
            'amount' => -$costPerImpression,
            'balance_after' => $ad->user->advertiserWallet->balance - $costPerImpression,
            'description' => "Ad impression: {$ad->title}",
            'reference_id' => $ad->id,
            'reference_type' => Advertisement::class,
            'status' => 'completed',
        ]);
        
        return true;
    }
    
    /**
     * Record ad click
     */
    public function recordClick(Advertisement $ad, User $user = null)
    {
        AdClick::create([
            'advertisement_id' => $ad->id,
            'user_id' => $user?->id,
            'ip_address' => request()->ip(),
        ]);
        
        $ad->increment('clicks');
        
        return true;
    }
    
    /**
     * Get system setting
     */
    protected function getSetting(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function() use ($key, $default) {
            return \App\Models\SystemSetting::where('key', $key)->value('value') ?? $default;
        });
    }
    
    /**
     * Get ad analytics
     */
    public function getAdAnalytics(Advertisement $ad)
    {
        $ctr = $ad->impressions > 0 
            ? ($ad->clicks / $ad->impressions) * 100 
            : 0;
            
        $cpc = $ad->clicks > 0 
            ? $ad->spent / $ad->clicks 
            : 0;
            
        $cpm = $ad->impressions > 0 
            ? ($ad->spent / $ad->impressions) * 1000 
            : 0;
        
        return [
            'impressions' => $ad->impressions,
            'clicks' => $ad->clicks,
            'spent' => $ad->spent,
            'remaining_budget' => $ad->budget - $ad->spent,
            'ctr' => round($ctr, 2), // Click-through rate
            'cpc' => round($cpc, 2), // Cost per click
            'cpm' => round($cpm, 2), // Cost per mille (1000 impressions)
            'daily_breakdown' => $this->getDailyBreakdown($ad),
        ];
    }
    
    protected function getDailyBreakdown(Advertisement $ad)
    {
        return AdImpression::where('advertisement_id', $ad->id)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as impressions')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }
}