<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">
        <!-- Interact with the `state` property in Alpine.js -->
        <div class="flex items-center space-x-2">
            <!-- Barcode Input Field -->
            <input
                type="text"
                id="barcodeInput"
                value="{{ $getState() }}"
                placeholder="Generated Barcode"
                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            />

            <!-- Generate Barcode Button -->
            <button
                type="button"
                onclick="generateBarcode()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest shadow-sm hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition"
            >
                Generate Barcode
            </button>
        </div>

        <div id="barcodePreview" class="mt-4">
            @if ($getState())
                <img id="barcodeImage" src="{{ $getBarcode() }}" alt="Barcode" />
            @endif
        </div>

        <script>
            function generateBarcode() {
                // Simple SKU-based barcode generation
                const skuInput = document.getElementById('barcodeInput');
                const sku = skuInput.value || Math.random().toString(36).substring(2, 8).toUpperCase();

                // Generate barcode image using JavaScript (or call your backend)
                const generator = new PicqerBarcodeGenerator(); // Assuming Picqer is included in your project
                const barcodeData = generator.getBarcode(sku, generator.TYPE_CODE_128);

                // Update the form field
                skuInput.value = sku;

                // Update the barcode preview
                const barcodeImage = document.getElementById('barcodeImage');
                barcodeImage.src = 'data:image/png;base64,' + barcodeData;
                document.getElementById('barcodePreview').style.display = 'block';
            }
        </script>
    </div>

</x-dynamic-component>
