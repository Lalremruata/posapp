<x-filament-panels::page>
    <div class="lg:flex">
        <div class="w-full lg:w-1/2 p-4">
            <x-filament::section>
                <x-filament-panels::form wire:submit="save">
                    {{ $this->form }}
                    <x-filament-panels::form.actions
                        :actions="$this->getFormActions()"
                    />
                </x-filament-panels::form>
            </x-filament::section>
            <div class="pt-2">
                    {{ $this->table }}
            </div>
        </div>
            <div class="w-full lg:w-1/2 p-4">
                <x-filament::section>


                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-lg">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row" class="px-6 py-4 text-lg text-red-500 whitespace-nowrap">
                                    Rs. {{$this->total}} /-
                                </th>
                                <th>
                                    {{$this->checkout}}
                                </th>

                                {{-- <td class="px-6 py-4">
                                    Silver
                                </td> --}}
                            </tr>
                        </tbody>
                    </table>
                </div>

                </x-filament::section>
            </div>
    </div>
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('formSaved', function () {
                const select = document.querySelector('[ref="productSelect"]');
                if (select) {
                    console.log('Saved');
                    select.focus();
                }
            });
        });
    </script>
    <x-filament-actions::modals />
</x-filament-panels::page>
