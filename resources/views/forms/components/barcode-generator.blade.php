<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{ barcode: $wire.entangle('{{ $getStatePath() }}') }"
        class="flex flex-1 items-center space-x-2"
    >
        <x-filament::input.wrapper>
            <x-filament::input
                type="text"
                x-model="barcode"
                class="block w-full"
            />
        </x-filament::input.wrapper>

        <div class="p-4">
            <x-filament::button @click="barcode = generateRandomBarcode(12)"
                size="sm"
                color="primary"
                icon="heroicon-m-plus">
                Generate Barcode
            </x-filament::button>
        </div>
    </div>

    <script>
        function generateRandomBarcode(length) {
            let barcode = '';
            for (let i = 0; i < length; i++) {
                barcode += Math.floor(Math.random() * 10);
            }
            return barcode;
        }
    </script>
</x-dynamic-component>
