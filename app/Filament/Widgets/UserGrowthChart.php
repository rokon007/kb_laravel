<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserGrowthChart extends ChartWidget
{
    // Remove static keyword completely
    protected ?string $heading = 'User Growth (Last 30 Days)';
    
    // This can remain static
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    // Or override getHeading method instead
    public function getHeading(): ?string
    {
        return 'User Growth (Last 30 Days)';
    }
}