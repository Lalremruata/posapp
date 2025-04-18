<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CategoryProfitChart;
use App\Filament\Widgets\LatestSales;
use App\Filament\Widgets\SalesPerMonthChart;
use App\Filament\Widgets\StatsOverview;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;
// Import any other widgets you want on the main dashboard

class MainDashboard extends BaseDashboard
{
    protected static string $routePath = 'dashboard';
    // Override the getWidgets method to specify exactly which widgets appear
    public function getWidgets(): array
    {
        return [
            Widgets\AccountWidget::class,
            StatsOverview::class,
            SalesPerMonthChart::class,
            LatestSales::class,
        ];
    }
}
