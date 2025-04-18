<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CategoryProfitChart;
use App\Filament\Widgets\MonthlyProfitChart;
use App\Filament\Widgets\ProductProfitTable;
use App\Filament\Widgets\ProfitStatsOverview;
use App\Filament\Widgets\TopProductsChart;
use Filament\Forms\Components\Grid;
use Filament\Pages\Dashboard;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Cache;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

class ProfitDashboard extends Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $routePath = 'profit-dashboard';
    protected static ?string $navigationLabel = 'Profit Dashboard';
    protected static ?string $title = 'Profit Dashboard';
    protected static ?string $slug = 'profit-dashboard';
    protected static ?int $navigationSort = 1;

    public function getColumns(): int | string | array
    {
        return [
            'md' => 3,
            'xl' => 3,
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('dateRange')
                ->label('Filter Date Range')
                ->icon('heroicon-m-calendar')
                ->form([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('dateFrom')
                                ->label('From Date')
                                ->default(now()->subMonths(6))
                                ->required(),
                            DatePicker::make('dateTo')
                                ->label('To Date')
                                ->default(now())
                                ->required()
                                ->afterOrEqual('dateFrom'),
                        ]),
                ])
                ->action(function (array $data): void {
                    // The dates are already in string format, so no need to call format()
                    $this->dispatch('dateRangeUpdated', [
                        'dateFrom' => $data['dateFrom'],
                        'dateTo' => $data['dateTo'],
                    ]);
                }),
        ];
    }


    // Define which widgets appear on this dashboard
    protected function getHeaderWidgets(): array
    {
        return [
            ProfitStatsOverview::class,
        ];
    }

    // Widgets displayed in the main content area
    public function getWidgets(): array
    {
        return [
            MonthlyProfitChart::class,
            CategoryProfitChart::class,
            ProductProfitTable::class,
            TopProductsChart::class,
        ];
    }
}
