<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product_name')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('product_description')
                ->rules(['max:200']),
            ImportColumn::make('category')
                ->relationship(),
//            ImportColumn::make('selling_price')
//                ->requiredMapping()
//                ->numeric()
//                ->rules(['required']),
//            ImportColumn::make('cost_price')
//                ->requiredMapping()
//                ->numeric()
//                ->rules(['required']),
            ImportColumn::make('barcode')
                ->rules(['max:50']),
            ImportColumn::make('supplier')
                ->relationship(),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // return Product::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Product();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

}
