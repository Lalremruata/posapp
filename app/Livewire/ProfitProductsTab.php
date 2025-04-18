<?php

namespace App\Livewire;

use App\Filament\Widgets\ProductProfitTable;
use App\Filament\Widgets\TopProductsChart;
use Livewire\Component;

class ProfitProductsTab extends Component
{
    public $dateFrom;
    public $dateTo;

    // Listen for the date range update event
    protected $listeners = ['dateRangeUpdated' => 'updateDateRange'];

    public function updateDateRange($dates)
    {
        $this->dateFrom = $dates['dateFrom'];
        $this->dateTo = $dates['dateTo'];

        // Use $refresh instead of trying to reference $this->id
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.profit-products-tab', [
            'widgets' => [
                ProductProfitTable::class,
                TopProductsChart::class,
            ],
        ]);
    }
}
