<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyProfitChart extends ChartWidget
{

    protected static ?string $heading = 'Monthly Profit Trend';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = [
        'default' => '2',
        'md' => '2',
        'xl' => '2',
    ];
    // Properties to store date filter values
    public $dateFrom;
    public $dateTo;

    protected function getDateFrom()
    {
        return $this->dateFrom ?? now()->subMonths(6)->format('Y-m-d');
    }

    protected function getDateTo()
    {
        return $this->dateTo ?? now()->format('Y-m-d');
    }

    // Reset chart when dates change
    public function updateDateRange($dates)
    {
        $this->dateFrom = $dates['dateFrom'];
        $this->dateTo = $dates['dateTo'];
    }
    protected function getData(): array
    {
        // Use the getter methods
        $startDate = $this->getDateFrom();
        $endDate = $this->getDateTo();

        // Your data fetching logic using $startDate and $endDate
        // ...

        // Example implementation
        $data = [];
        $labels = [];

        // Generate all months in the range
        $startObj = date_create($startDate);
        $endObj = date_create($endDate);

        $months = [];

        // Generate all months in the range
        for ($date = clone $startObj; $date <= $endObj; date_modify($date, '+1 month')) {
            $monthKey = $date->format('n');
            $months[$monthKey] = [
                'label' => $date->format('M Y'),
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

        // Extract data and labels
        foreach ($months as $month) {
            $data[] = $month['profit'];
            $labels[] = $month['label'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Profit',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    // Method to receive filter updates from dashboard
    protected function getListeners(): array
    {
        return ['dateRangeUpdated' => 'updateDateRange'];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
