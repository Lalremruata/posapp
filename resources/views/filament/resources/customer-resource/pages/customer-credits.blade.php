<x-filament-panels::page>
    <div class="mb-6">
        <h1 class="text-2xl font-bold">{{ $this->customer }}'s Credit Account</h1>
    </div>

    <x-filament::section>
        <form wire:submit="submitCredit">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit">
                    Submit
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
