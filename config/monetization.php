<?php

return [
    // Admin revenue share percentage
    'admin_share_percentage' => 40, // 40%
    
    // Creator revenue share percentage
    'creator_share_percentage' => 60, // 60%
    
    // Minimum eligibility criteria for creators
    'eligibility' => [
        'min_followers' => 1000,
        'min_posts' => 50,
        'min_total_views' => 10000,
        'account_age_days' => 30,
    ],
    
    // Earning rates (in BDT)
    'rates' => [
        // Video views
        'video_view' => 0.05, // 0.05 BDT per view
        
        // Reel views
        'reel_view' => 0.03, // 0.03 BDT per view
        
        // Ad impressions (when ad shows on creator's content)
        'ad_impression' => 0.10, // 0.10 BDT per impression
        
        // Post engagement (like + comment + share weighted)
        'engagement' => 0.02, // 0.02 BDT per engagement
    ],
    
    // Minimum withdrawal amount
    'min_withdrawal' => 500, // 500 BDT
    
    // Payment processing fee
    'withdrawal_fee_percentage' => 2, // 2%
];