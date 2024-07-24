<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesPerMonthChart extends ChartWidget
{
    protected static ?string $heading = 'Orders per month';

    protected static ?int $sort = 1;

    protected function getData(): array
{
    // Get sales data grouped by month
    $salesData = Sale::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_amount) as total_sales')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

    // Initialize the data array for each month with default value of 0
    $monthlySales = array_fill(1, 12, 0);

    // Fill monthly sales data
    foreach ($salesData as $data) {
        $monthlySales[$data->month] = $data->total_sales;
    }

    return [
        'datasets' => [
            [
                'label' => 'Orders',
                'data' => array_values($monthlySales),
                'fill' => 'start',
            ],
        ],
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    ];
}

    protected function getType(): string
    {
        return 'line';
    }
}
