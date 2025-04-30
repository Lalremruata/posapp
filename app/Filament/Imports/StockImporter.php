<?php

namespace App\Filament\Imports;

use App\Models\Stock;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class StockImporter extends Importer
{
    protected static ?string $model = Stock::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('store')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('product')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('selling_price')
                ->numeric()
                ->rules(['required']),
            ImportColumn::make('cost_price')
                ->numeric()
                ->rules(['required']),
        ];
    }

    public function resolveRecord(): ?Stock
    {
        // return Stock::firstOrNew([
        //     // Update existing records, matching them by `$this->data['column_name']`
        //     'email' => $this->data['email'],
        // ]);

        return new Stock();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your stock import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
