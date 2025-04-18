<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopProductsChart extends ChartWidget
{

    protected static bool $isLazy = true;

    protected static ?string $heading = 'Top 5 Products by Profit';
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = [
        'default' => '2',
        'md' => '2',
        'xl' => '2',
    ];

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

    protected function getData(): array
    {
        // Use the getter methods
        $startDate = $this->getDateFrom();
        $endDate = $this->getDateTo();

        $products = Product::select([
            'products.product_name',
            DB::raw('SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as profit')
        ])
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name')
            ->orderByDesc('profit')
            ->take(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Profit',
                    'data' => $products->pluck('profit')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $products->pluck('product_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
