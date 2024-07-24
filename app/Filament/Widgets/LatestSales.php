<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSales extends BaseWidget
{
    protected static ?int $sort = 2;
    public function table(Table $table): Table
    {
        return $table
        ->query(
            SaleItem::query()
        )
        ->columns([
            TextColumn::make('')
            ->weight(FontWeight::Bold)
            ->rowIndex(),
            TextColumn::make('product.product_name'),
            TextColumn::make('sale.store.store_name'),
            TextColumn::make('quantity'),
            TextColumn::make('created_at')
                ->label('date')
                ->date()
                ->sortable(),
        ]);
    }
}
