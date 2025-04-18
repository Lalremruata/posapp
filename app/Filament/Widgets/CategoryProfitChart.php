<?php

namespace App\Filament\Widgets;

use App\Models\ProductCategory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryProfitChart extends ChartWidget
{
//    public static function canView(): bool
//    {
//        // Only show this widget on the profit dashboard
//        return str_contains(request()->path(), 'profit-dashboard');
//    }
    protected static ?string $heading = 'Profit by Category';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = [
        'default' => '2',
        'md' => '2',
        'xl' => '2',
    ];
    protected function getData(): array
    {
        $startDate = now()->subMonths(6);
        $endDate = now();

        $categories = DB::table('product_categories')
            ->join('products', 'product_categories.id', '=', 'products.category_id')
            ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select(
                'product_categories.category_name',
                DB::raw('SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as total_profit')
            )
            ->groupBy('product_categories.id', 'product_categories.category_name')
            ->orderByDesc('total_profit')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Profit by Category',
                    'data' => $categories->pluck('total_profit')->toArray(),
                    'backgroundColor' => ['#0088FE', '#00C49F', '#FFBB28', '#FF8042'],
                ],
            ],
            'labels' => $categories->pluck('category_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
