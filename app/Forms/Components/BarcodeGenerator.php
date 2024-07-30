<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class BarcodeGenerator extends Field
{
    protected string $view = 'forms.components.barcode-generator';

    public static function make(string $name): static
    {
        return (new static($name));
    }
}
