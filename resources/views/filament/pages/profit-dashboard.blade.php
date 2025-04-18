<x-filament-panels::page>
    <!-- Date Filter -->
    <div class="mb-6 p-4 bg-white rounded shadow">
        <h3 class="text-lg font-medium mb-3">Date Range</h3>

        {{ $this->form }}

        <div class="mt-4 flex space-x-4">
            <x-filament::button
                wire:click="updateDateRange"
                type="button"
            >
                Apply Filter
            </x-filament::button>

            <x-filament::button
                wire:click="resetDateRange"
                color="secondary"
                type="button"
            >
                Reset
            </x-filament::button>
        </div>
    </div>

    <!-- Header Widgets -->
{{--    <div class="mb-6">--}}
{{--        @foreach($this->getHeaderWidgets() as $widget)--}}
{{--            @livewire($widget)--}}
{{--        @endforeach--}}
{{--    </div>--}}

    <!-- Tab Navigation -->
    <div class="flex border-b mb-6 overflow-x-auto">
        <button
            wire:click="setActiveTab('overview')"
            type="button"
            class="px-4 py-2 font-medium whitespace-nowrap {{ $activeTab === 'overview' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}"
        >
            <x-heroicon-o-presentation-chart-line class="w-5 h-5 inline-block mr-1" />
            Overview
        </button>

        <button
            wire:click="setActiveTab('products')"
            type="button"
            class="px-4 py-2 font-medium whitespace-nowrap {{ $activeTab === 'products' ? 'text-primary-600 border-b-2 border-primary-600' : 'text-gray-500 hover:text-gray-700' }}"
        >
            <x-heroicon-o-shopping-bag class="w-5 h-5 inline-block mr-1" />
            Products
        </button>
    </div>

    <!-- Tab Content -->
    <div class="w-full">
        @if($activeTab === 'overview')
            @livewire('profit-overview-tab')
        @elseif($activeTab === 'products')
            @livewire('profit-products-tab')
        @endif
    </div>
</x-filament-panels::page>
