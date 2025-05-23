<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total items', Product::count())
                // ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Total Sales', SaleItem::count())
                ->color('success')
                // ->description('7% increase')
                // ->descriptionIcon('heroicon-m-arrow-trending-down'),
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('primary'),
            Stat::make('Total Sales Today', SaleItem::whereDate('created_at',today())->count())
                // ->description('3% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),
        ];
    }
}
