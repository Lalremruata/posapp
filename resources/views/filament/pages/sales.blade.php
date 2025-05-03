<x-filament-panels::page>
    <!-- Top section with side-by-side columns -->
    <div class="lg:flex">
        <div class="w-full">
            <x-filament::section>
                <x-filament-panels::form wire:submit="save">
                    <!-- Barcode Scanner Section -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold mb-2">Barcode Scanner</h3>
                        <div class="flex items-center">
                            <div class="flex-1">
                                <!-- We specifically target the barcode input field -->
                                <div
                                    x-data="{}"
                                    x-init="$nextTick(() => {
                                        // Find the input inside our container
                                        const input = $refs.barcodeInput.querySelector('input');
                                        if (input) {
                                            input.focus();

                                            // Listen for clicks anywhere on the page to refocus
                                            document.addEventListener('click', function(e) {
                                                // Don't refocus if clicking on another input or button
                                                if (!e.target.matches('input, button, select, textarea, [role=button]')) {
                                                    input.focus();
                                                }
                                            });
                                        }
                                    })"
                                >
                                    <!-- Barcode input field -->
                                    <!-- We render the barcode input separately to give it focus -->
                                    <div class="relative w-full" x-ref="barcodeInput">
                                        @php
                                            $barcodeField = null;
                                            foreach ($this->form->getFlatComponents() as $component) {
                                                if ($component->getName() === 'barcode') {
                                                    $barcodeField = $component;
                                                    break;
                                                }
                                            }
                                        @endphp

                                        @if ($barcodeField)
                                            {{ $barcodeField }}
                                        @else
                                            <!-- Fallback if barcode field not found -->
                                            <input
                                                type="text"
                                                placeholder="Scan barcode here"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                                wire:model.live="data.barcode"
                                            >
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Status indicator -->
                            <div class="ml-3 flex items-center">
                                <div class="flex items-center">
                                    <span class="relative flex h-3 w-3 mr-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                    </span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Ready to scan</span>
                                </div>
                            </div>
                        </div>

                        <!-- Last scanned item info - shows briefly when product is scanned -->
                        <div
                            x-data="{ show: false, productName: '', quantity: 0 }"
                            x-on:product-scanned.window="
                                show = true;
                                productName = $event.detail.productName;
                                quantity = $event.detail.quantity;
                                setTimeout(() => show = false, 3000);
                            "
                            x-show="show"
                            x-transition
                            class="mt-3 p-2 bg-green-50 dark:bg-green-900 text-green-800 dark:text-green-100 text-sm rounded-md"
                        >
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Added: <span x-text="productName"></span> (Qty: <span x-text="quantity"></span>)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rest of the form (manual product selection) -->
                    <div class="mt-4">
                        {{ $this->form }}
                    </div>

{{--                    <x-filament-panels::form.actions--}}
{{--                        :actions="$this->getFormActions()"--}}
{{--                    />--}}
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
            // Listen for the form saved event
            Livewire.on('formSaved', function () {
                // Focus back on the barcode input field
                setTimeout(function() {
                    // Look for the input within our barcode container
                    const barcodeContainer = document.querySelector('[x-ref="barcodeInput"]');
                    if (barcodeContainer) {
                        const barcodeInput = barcodeContainer.querySelector('input');
                        if (barcodeInput) {
                            barcodeInput.focus();
                        } else {
                            console.log('Barcode input element not found');
                        }
                    } else {
                        // Alternative selector approach - find by name
                        const barcodeInputByName = document.querySelector('input[name="data[barcode]"]');
                        if (barcodeInputByName) {
                            barcodeInputByName.focus();
                        } else {
                            console.log('Barcode input not found by any method');
                        }
                    }
                }, 100);
            });

            // Listen for product added notification and dispatch custom event
            Livewire.on('notify', function (notification) {
                if (notification.message && notification.message.includes('Product added') ||
                    notification.message.includes('quantity updated')) {

                    // Get the product name from the table if possible
                    let productName = 'Product';
                    let quantity = '1';

                    // Try to get the most recently added item from the table
                    const tableRows = document.querySelectorAll('table tbody tr');
                    if (tableRows.length > 0) {
                        const lastRow = tableRows[0]; // First row is usually the most recent
                        const productNameCell = lastRow.querySelector('td:first-child');
                        const quantityCell = lastRow.querySelector('td:nth-child(3)');

                        if (productNameCell) {
                            productName = productNameCell.textContent.trim();
                        }

                        if (quantityCell) {
                            quantity = quantityCell.textContent.trim();
                        }
                    }

                    // Dispatch a custom event for the UI to show what was just scanned
                    window.dispatchEvent(new CustomEvent('product-scanned', {
                        detail: {
                            productName: productName,
                            quantity: quantity
                        }
                    }));
                }
            });

            // Create a keyboard shortcut to quickly focus the barcode input
            document.addEventListener('keydown', function(e) {
                // Alt+B or Ctrl+B to focus barcode input
                if ((e.altKey || e.ctrlKey) && e.key === 'b') {
                    e.preventDefault();

                    // Try multiple methods to find the barcode input
                    const barcodeContainer = document.querySelector('[x-ref="barcodeInput"]');
                    if (barcodeContainer) {
                        const input = barcodeContainer.querySelector('input');
                        if (input) {
                            input.focus();
                            return;
                        }
                    }

                    // Backup method - try to find by name
                    const barcodeInputByName = document.querySelector('input[name="data[barcode]"]');
                    if (barcodeInputByName) {
                        barcodeInputByName.focus();
                    }
                }
            });
        });
    </script>
    @endscript
    <x-filament-actions::modals />
</x-filament-panels::page>
