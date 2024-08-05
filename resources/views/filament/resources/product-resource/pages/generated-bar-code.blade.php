<x-filament-panels::page>
    <!-- Display Barcode Image -->
    <div class="flex flex-col items-center p-4 space-y-4">
        <!-- Title -->
        <h1 class="text-2xl font-semibold mb-4">{{$this->getRecord()->product_name}} Barcode</h1>

        <!-- Barcode Image -->
        <div class="flex">
            <span class="text-lg mt-2 font-mono" id="productName">{{$this->getRecord()->product_name}}</span>
            <span class="text-lg mt-2 font-mono" id="sellingPrice">{{$this->getRecord()->selling_price}}</span>
        </div>
        <img id="barcodeImage" src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode" />
        <span class="text-lg mt-2 font-mono" id="barcodeText">{{$this->getRecord()->barcode}}</span>

        <!-- Download Barcode Button -->
        <x-filament::button
            href="#"
            onclick="downloadBarcode()"
            tag="a"
            color="success"
            size="lg"
            icon="heroicon-o-arrow-down-on-square"
            class="mt-4 px-4 py-2"
        >
            Download Barcode
        </x-filament::button>

        <!-- Print Barcode Button -->
        <x-filament::button
            onclick="printBarcode()"
            color="warning"
            size="lg"
            icon="heroicon-o-printer"
            class="mt-4 px-4 py-2"
        >
        Print Barcode
        </x-filament::button>

    </div>

    <!-- Styles for Printing -->
    <style>
        /* Style for barcode rendering */
        #barcodeImage {
            max-width: 100%; /* Ensure the image is responsive */
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* General styles for printing */
        @media print {
    button, a {
        display: none;
    }
    body * {
        visibility: hidden;
    }
    #barcodeImage, #barcodeImage *, #productName, #sellingPrice, #barcodeText {
        visibility: visible;
    }
    #barcodeImage, h1, #productName, #sellingPrice, #barcodeText {
        position: relative;
        display: block;
        margin: 0 auto;
        width: auto;
        height: auto;
        max-width: 200mm;
        max-height: 100mm;
        border: none;
        padding: 0;
        box-shadow: none;
        text-align: center;
    }
    .text-lg {
        font-size: 12pt;
        text-align: center;
        visibility: visible;
    }
            /* Avoid page break issues for barcode display */
            @page {
                margin: 10mm; /* Remove default margins */
                size: auto; /* Use the paper's size */
            }
        }
    </style>

    <!-- JavaScript for Printing -->
    <script>
         function downloadBarcode() {
            const canvas = document.createElement('canvas');
            const imgElement = document.getElementById('barcodeImage');
            const barcodeTextElement = document.getElementById('barcodeText');
            const productNameElement = document.getElementById('productName');
            const sellingPriceElement = document.getElementById('sellingPrice');

            // Set canvas size
            const padding = 50; // Add some padding for aesthetics
            canvas.width = Math.max(imgElement.naturalWidth, 400) + padding; // Set minimum width
            canvas.height = imgElement.naturalHeight + 100 + padding; // Extra space for text

            const ctx = canvas.getContext('2d');
            // Fill background
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);


            // Set text properties
            ctx.font = '16px monospace';
            ctx.textAlign = 'center';
            ctx.fillStyle = 'black';

            // Draw the product name above the image
            ctx.fillText(productNameElement.textContent, canvas.width / 2, 30);

            // Draw the selling price below the product name
            ctx.fillText(sellingPriceElement.textContent, canvas.width / 2, 60);

            // Draw the barcode image on the canvas
             const barcodeX = (canvas.width - imgElement.naturalWidth) / 2;
            ctx.drawImage(imgElement, barcodeX, 80);
            // Draw the barcode text below the image
            ctx.fillText(barcodeTextElement.textContent, canvas.width / 2, imgElement.naturalHeight + 100);

            // Convert canvas to data URL
            const link = document.createElement('a');
            link.download = 'barcode.png';
            link.href = canvas.toDataURL('image/png');

            // Trigger download
            link.click();
        }
        function printBarcode() {
            window.print();
        }
    </script>
</x-filament-panels::page>
