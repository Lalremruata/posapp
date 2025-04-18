<?php

namespace App\Livewire;

use App\Filament\Widgets\CategoryProfitChart;
use App\Filament\Widgets\MonthlyProfitChart;
use Livewire\Component;

class ProfitOverviewTab extends Component
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
        return view('livewire.profit-overview-tab', [
            'widgets' => [
                MonthlyProfitChart::class,
                CategoryProfitChart::class,
            ],
        ]);
    }
}
