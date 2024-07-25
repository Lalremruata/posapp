<?php

namespace App\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeGenerator extends Field
{
    protected string $view = 'forms.components.barcode-generator';
    public function getBarcode(): string
    {
        $generator = new BarcodeGeneratorPNG();
        return 'data:image/png;base64,' . base64_encode($generator->getBarcode($this->getState(), $generator::TYPE_CODE_128));
    }

    public static function make(string $name): static
    {
        return parent::make($name)
            ->afterStateUpdated(function (Closure $get, Closure $set, $state) {
                if ($state) {
                    $generator = new BarcodeGeneratorPNG();
                    $barcode = 'data:image/png;base64,' . base64_encode($generator->getBarcode($state, $generator::TYPE_CODE_128));
                    $set('barcode', $barcode);
                }
            });
    }
}
