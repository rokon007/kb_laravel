<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Post;
use App\Models\Advertisement;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        
        $totalPosts = Post::count();
        $totalViews = Post::sum('views_count');
        
        $activeAds = Advertisement::where('status', 'running')->count();
        $adsRevenue = Transaction::where('type', 'ad_spend')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        
        $creatorPayouts = Transaction::where('type', 'earning')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');
        
        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description("{$newUsersToday} new today")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
                
            Stat::make('Total Posts', number_format($totalPosts))
                ->description(number_format($totalViews) . ' total views')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),
                
            Stat::make('Active Ads', $activeAds)
                ->description('৳' . number_format($adsRevenue, 2) . ' revenue this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
                
            Stat::make('Creator Payouts', '৳' . number_format($creatorPayouts, 2))
                ->description('This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
        ];
    }
}