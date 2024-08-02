<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class BarcodeGenerator extends Field
{
    protected string $view = 'forms.components.barcode-generator';
    public function getStatePath(bool $isAbsolute = true): string
    {
        return parent::getStatePath($isAbsolute);
    }
}
