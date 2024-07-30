<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Picqer\Barcode\BarcodeGeneratorPNG;

class GeneratedBarCode extends Page
{
    use InteractsWithRecord;
    protected static string $resource = ProductResource::class;

    protected static string $view = 'filament.resources.product-resource.pages.generated-bar-code';
    public $barcodeImage;
    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $barcode=Product::where('id', $record)->pluck('barcode')->first();
        $this->showBarcode($barcode);
    }
    public function showBarcode($barcode)
    {
        $generator = new BarcodeGeneratorPNG();
        $barcodeData = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);
        $this->barcodeImage = base64_encode($barcodeData);
    }

}
