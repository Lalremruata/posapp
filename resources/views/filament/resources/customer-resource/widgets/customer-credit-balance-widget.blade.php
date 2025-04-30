<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight">Credit Balance</h2>
                <p class="text-sm text-gray-500">Current available balance</p>
            </div>
            <div>
                {{-- Using the balance passed from the widget --}}
                <span class="text-3xl font-bold text-primary-600">{{ number_format($balance, 2) }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
