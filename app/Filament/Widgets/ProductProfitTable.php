<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProductProfitTable extends BaseWidget
{

    protected int | string | array $columnSpan = [
        'default' => '2',
        'md' => '2',
        'xl' => '2',
    ];
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with('category')
                    ->select([
                        'products.id',
                        'products.product_name as name',
                        'products.category_id',
                        DB::raw('SUM(sale_items.selling_price * sale_items.quantity) as revenue'),
                        DB::raw('SUM(sale_items.cost_price * sale_items.quantity) as cost'),
                        DB::raw('SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) as profit'),
                        DB::raw('SUM(sale_items.quantity) as units_sold'),
                        DB::raw('CASE WHEN SUM(sale_items.selling_price * sale_items.quantity) > 0
                        THEN (SUM((sale_items.selling_price * sale_items.quantity) - (sale_items.cost_price * sale_items.quantity)) /
                            SUM(sale_items.selling_price * sale_items.quantity)) * 100
                        ELSE 0 END as margin')
                    ])
                    ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
                    ->leftJoin('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.created_at', [now()->subMonths(6), now()])
                    ->groupBy(['products.id',
                        'products.product_name',
                        'products.category_id',
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.category_name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('revenue')
                    ->money('inr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost')
                    ->money('inr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit')
                    ->money('inr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('margin')
                    ->label('Margin %')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string =>
                    floatval($state) > 50 ? 'success' :
                        (floatval($state) > 30 ? 'warning' : 'danger')
                    ),
                Tables\Columns\TextColumn::make('units_sold')
                    ->label('Units Sold')
                    ->sortable(),
            ])
            ->defaultSort('profit', 'desc')
            ->paginated([10, 25, 50]);
    }
}
