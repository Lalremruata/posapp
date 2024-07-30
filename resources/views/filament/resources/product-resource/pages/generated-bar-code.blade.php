<x-filament-panels::page>
    <!-- Display Barcode Image -->
    <div class="flex flex-col items-center p-4 space-y-4">
        <!-- Title -->
        <h1 class="text-2xl font-semibold mb-4">{{$this->getRecord()->product_name}} Barcode</h1>

        <!-- Barcode Image -->
        <img id="barcodeImage" src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode" />
        <span class="text-lg mt-2 font-mono">{{$this->getRecord()->barcode}}</span>

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
            /* Hide print button when printing */
            button, a {
                display: none;
            }

            /* Ensure barcode is centered on printed page */
            body * {
                visibility: hidden;
            }

            #barcodeImage, #barcodeImage * {
                visibility: visible;
            }

            #barcodeImage,h1 {
                position: relative; /* Ensure it stays within page boundaries */
                display: block;
                /* margin: 0 auto; Center horizontally on the page */
                width: auto; /* Maintain original width */
                height: auto; /* Maintain aspect ratio */
                max-width: 200mm; /* Set a maximum width to prevent stretching */
                max-height: 100mm; /* Set a maximum height to prevent stretching */
                border: none; /* Remove border for printing */
                padding: 0;
                box-shadow: none;
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
            const textElement = document.querySelector('.text-lg');

            // Set canvas size
            canvas.width = imgElement.naturalWidth;
            canvas.height = imgElement.naturalHeight + 30; // Extra space for text

            const ctx = canvas.getContext('2d');

            // Draw the barcode image on the canvas
            ctx.drawImage(imgElement, 0, 0);

            // Set text properties
            ctx.font = '16px monospace';
            ctx.textAlign = 'center';
            ctx.fillStyle = 'black';

            // Draw the barcode text below the image
            ctx.fillText(textElement.textContent, canvas.width / 2, imgElement.naturalHeight + 20);

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
