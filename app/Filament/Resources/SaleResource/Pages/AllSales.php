<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Credit;
use App\Models\SaleItem;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AllSales extends Page implements HasTable
{

    use InteractsWithTable;
    protected static string $resource = SaleResource::class;

    protected static string $view = 'filament.resources.sale-resource.pages.all-sales';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SaleItem::query()
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.product_name')
                    ->searchable()
                    ->label('Product'),
                TextColumn::make('quantity'),
                TextColumn::make('price'),
                TextColumn::make('sale_date')
                    ->dateTime()
                    ->sortable()
            ]);
            }

}
