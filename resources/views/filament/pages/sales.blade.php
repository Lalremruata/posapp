<x-filament-panels::page>
    <!-- Top section with side-by-side columns -->
    <div class="lg:flex">
        <div class="w-full">
            <x-filament::section>
                <x-filament-panels::form wire:submit="save">
                    {{ $this->form }}
                    <x-filament-panels::form.actions
                        :actions="$this->getFormActions()"
                    />
                </x-filament-panels::form>
            </x-filament::section>
        </div>
        <div class="w-full lg:w-1/3 p-2">
            <x-filament::section>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-lg">
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-lg text-right">
                                {{$this->checkout}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <th scope="row" class="px-6 py-4 text-lg text-red-500 whitespace-nowrap">
                                Rs. {{$this->total}} /-
                            </th>
                            <th>

                            </th>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>

    <!-- Bottom section with full-width table -->
    <div class="w-full">
        <x-filament::section>
            {{ $this->table }}
        </x-filament::section>
    </div>

    @script
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('formSaved', function () {
                // Focus on the product search field after adding to cart
                setTimeout(function() {
                    // Find the product search select element
                    const searchInput = document.querySelector('.fi-select-input');
                    if (searchInput) {
                        searchInput.focus();
                        // Also try to clear any previous search value if possible
                        try {
                            const clearButton = document.querySelector('.fi-select-clear-button');
                            if (clearButton) {
                                clearButton.click();
                            }
                        } catch (e) {
                            console.log('Clear button not found');
                        }
                    } else {
                        console.log('Product search input not found');
                    }
                }, 100);
            });
        });
    </script>
    @endscript
    <x-filament-actions::modals />
</x-filament-panels::page>
