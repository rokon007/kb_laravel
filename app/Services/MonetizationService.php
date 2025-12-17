<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Advertisement;
use App\Models\CreatorEarning;
use App\Models\Transaction;

class MonetizationService
{
    /**
     * Calculate earning for a video/reel view
     */
    public function calculateViewEarning(Post $post, Advertisement $ad = null)
    {
        $creator = $post->user;
        
        // Check if creator is eligible
        if (!$this->isCreatorEligible($creator)) {
            return false;
        }
        
        // Calculate base earning based on content type
        $baseRate = $post->type === 'reel' 
            ? config('monetization.rates.reel_view')
            : config('monetization.rates.video_view');
            
        // If ad was shown, add ad impression earning
        $adEarning = $ad ? config('monetization.rates.ad_impression') : 0;
        
        $totalEarning = $baseRate + $adEarning;
        
        // Calculate split
        $adminShare = $totalEarning * (config('monetization.admin_share_percentage') / 100);
        $creatorShare = $totalEarning * (config('monetization.creator_share_percentage') / 100);
        
        // Record earning
        $earning = CreatorEarning::create([
            'user_id' => $creator->id,
            'post_id' => $post->id,
            'advertisement_id' => $ad?->id,
            'type' => 'view',
            'amount' => $totalEarning,
            'admin_share' => $adminShare,
            'creator_share' => $creatorShare,
        ]);
        
        // Update creator balance
        $creator->increment('balance', $creatorShare);
        $creator->increment('total_earned', $creatorShare);
        
        // Create transaction record
        Transaction::create([
            'user_id' => $creator->id,
            'wallet_id' => $creator->wallet->id,
            'type' => 'earning',
            'amount' => $creatorShare,
            'balance_after' => $creator->balance,
            'description' => 'Earning from ' . ($post->type === 'reel' ? 'reel' : 'video') . ' view',
            'reference_id' => $earning->id,
            'reference_type' => CreatorEarning::class,
            'status' => 'completed',
        ]);
        
        return $earning;
    }
    
    /**
     * Check if user is eligible for monetization
     */

    public function isCreatorEligible(User $user): bool
    {
        // Already approved creator
        if ($user->is_creator && $user->creator_approved_at) {
            return true;
        }
        
        $config = config('monetization.eligibility');
        
        // Check followers count
        if ($user->followers_count < $config['min_followers']) {
            return false;
        }
        
        // Check total posts
        $totalPosts = $user->posts()->count();
        if ($totalPosts < $config['min_posts']) {
            return false;
        }
        
        // Check total views
        $totalViews = $user->posts()->sum('views_count');
        if ($totalViews < $config['min_total_views']) {
            return false;
        }
        
        // Check account age
        $accountAgeDays = $user->created_at->diffInDays(now());
        if ($accountAgeDays < $config['account_age_days']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Apply for creator monetization
     */
    public function applyForCreatorProgram(User $user)
    {
        if (!$this->isCreatorEligible($user)) {
            throw new \Exception('User does not meet eligibility criteria');
        }
        
        // Update user status to pending approval
        $user->update([
            'is_creator' => true,
            'creator_approved_at' => null, // Pending admin approval
        ]);
        
        // Notify admin
        // ... notification logic
        
        return true;
    }
    
    /**
     * Calculate total earnings for a creator
     */
    public function getCreatorEarningSummary(User $creator, $startDate = null, $endDate = null)
    {
        $query = CreatorEarning::where('user_id', $creator->id);
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        return [
            'total_earnings' => $query->sum('creator_share'),
            'total_views' => $query->where('type', 'view')->count(),
            'total_impressions' => $query->where('type', 'impression')->count(),
            'average_per_view' => $query->where('type', 'view')->avg('creator_share'),
            'breakdown' => [
                'video_earnings' => $query->whereHas('post', function($q) {
                    $q->where('type', 'video');
                })->sum('creator_share'),
                'reel_earnings' => $query->whereHas('post', function($q) {
                    $q->where('type', 'reel');
                })->sum('creator_share'),
                'ad_impression_earnings' => $query->whereNotNull('advertisement_id')->sum('creator_share'),
            ],
        ];
    }
}