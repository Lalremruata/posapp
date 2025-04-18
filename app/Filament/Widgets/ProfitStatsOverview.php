<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProfitStatsOverview extends BaseWidget
{

    // Properties to store date filter values
    public $dateFrom;
    public $dateTo;

    // Method to receive filter updates from dashboard
    protected function getListeners(): array
    {
        return ['dateRangeUpdated' => 'updateDateRange'];
    }

    // Reset chart when dates change
    public function updateDateRange($dates)
    {
        $this->dateFrom = $dates['dateFrom'];
        $this->dateTo = $dates['dateTo'];
    }

    protected function getDateFrom()
    {
        return $this->dateFrom ?? now()->subMonths(6)->format('Y-m-d');
    }

    protected function getDateTo()
    {
        return $this->dateTo ?? now()->format('Y-m-d');
    }

    protected function getStats(): array
    {
        // Get date ranges using the getter methods
        $startDate = $this->getDateFrom();
        $endDate = $this->getDateTo();

        // For previous period, use same duration but earlier
        $dateDiff = strtotime($endDate) - strtotime($startDate);
        $prevEndDate = date('Y-m-d', strtotime($startDate) - 86400); // 1 day before start date
        $prevStartDate = date('Y-m-d', strtotime($prevEndDate) - $dateDiff);

        // Calculate total profit for current period
        $totalProfit = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->selectRaw('SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as total_profit')
            ->value('total_profit') ?? 0;

        // Calculate total profit for previous period
        $previousPeriodProfit = SaleItem::whereHas('sale', function ($query) use ($prevStartDate, $prevEndDate) {
            $query->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
        })
            ->sum(DB::raw('(selling_price * quantity) - (cost_price * quantity)'));

        $profitChange = $previousPeriodProfit ? (($totalProfit - $previousPeriodProfit) / $previousPeriodProfit) * 100 : 0;

        // Calculate average profit margin
        $totalRevenue = SaleItem::whereHas('sale', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->sum(DB::raw('selling_price * quantity'));

        $avgMargin = $totalRevenue ? ($totalProfit / $totalRevenue) * 100 : 0;

        // Find top profit category
        $topCategory = DB::table('product_categories')
            ->join('products', 'product_categories.id', '=', 'products.category_id')
            ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select(
                'product_categories.category_name as name',
                DB::raw('SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as total_profit')
            )
            ->groupBy('product_categories.id', 'product_categories.category_name')
            ->orderByDesc('total_profit')
            ->first();

        // Get monthly profit data for chart
        $startObj = date_create($startDate);
        $endObj = date_create($endDate);

        $months = [];

        // Generate all months in the range
        for ($date = clone $startObj; $date <= $endObj; date_modify($date, '+1 month')) {
            $monthKey = $date->format('n');
            $months[$monthKey] = [
                'label' => $date->format('M'),
                'profit' => 0,
            ];
        }

        // Get profit data by month
        $profits = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->selectRaw('MONTH(sales.created_at) as month, SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as profit')
            ->groupBy('month')
            ->get();

        // Map the profits to the months
        foreach ($profits as $profit) {
            if (isset($months[$profit->month])) {
                $months[$profit->month]['profit'] = $profit->profit;
            }
        }

        $monthlyProfits = array_column($months, 'profit');

        return [
            Stat::make('Total Profit', '₹' . number_format($totalProfit, 2))
                ->description($profitChange >= 0 ? number_format($profitChange, 1) . '% increase' : number_format(abs($profitChange), 1) . '% decrease')
                ->descriptionIcon($profitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($profitChange >= 0 ? 'success' : 'danger')
                ->chart($monthlyProfits),

            Stat::make('Average Profit Margin', number_format($avgMargin, 1) . '%')
                ->icon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Top Profit Category', $topCategory?->name ?? 'N/A')
                ->description('₹' . number_format($topCategory?->total_profit ?? 0, 2) . ' total profit')
                ->icon('heroicon-m-shopping-bag')
                ->color('warning'),
        ];
    }
}
