<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Imports\StockImporter;
use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Stock;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component as Livewire;
use Illuminate\Database\Query\Builder As QueryBuilder;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Manage Stocks';
    public static function getEloquentQuery(): Builder
    {
        if(auth()->user()->roles->first()->name == 'Admin') {
            return parent::getEloquentQuery()->withoutGlobalScopes();
        }
        else {
            return parent::getEloquentQuery()->where('store_id', auth()->user()->store_id);

        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
            ->schema([
                Forms\Components\Select::make('store_id')
                    ->label('Select Store')
                    ->options(function(){
                        if(auth()->user()->roles->first()->name == 'Admin')
                        {
                            return Store::all()->pluck('store_name', 'id');
                        }
                        else
                            return Store::where('id',auth()->user()->store_id)->pluck('store_name', 'id');


                    })
                    ->default(function(){
                        return Store::where('id',auth()->user()->store_id)->first()?->id;
                    })
                    ->searchable()
                    ->required(),
            ]),
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Select Category')
                            ->options(ProductCategory::pluck('category_name','id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Livewire $livewire, Forms\Set $set) {
                                $set('product_id', null);
                            })
                            ->reactive()
                            ->afterStateHydrated(function (Forms\Set $set, $state, ?Model $record) {
                                if ($record && !$state) {
                                    // If we're editing a record and category_id isn't set
                                    $product = Product::find($record->product_id);
                                    if ($product) {
                                        $set('category_id', $product->category_id);
                                    }
                                }
                            }),
                        Forms\Components\Select::make('product_id')
                            ->label('Select Product')
                            ->options(function (Forms\Get $get) {
                                $categoryId = $get('category_id');

                                if (!$categoryId) {
                                    return [];
                                }

                                return Product::where('category_id', $categoryId)
                                    ->orderBy('product_name')
                                    ->orderBy('product_description')
                                    ->get()
                                    ->pluck('product_info', 'id');
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $product = Product::find($value);

                                if (!$product) {
                                    return null;
                                }

                                return "{$product->product_name} ({$product->product_description})";
                            })
                            ->getSearchResultsUsing(function (string $search, Forms\Get $get) {
                                $categoryId = $get('category_id');

                                if (!$categoryId) {
                                    return [];
                                }

                                return Product::where('category_id', $categoryId)
                                    ->where(function ($query) use ($search) {
                                        $query->where('product_name', 'like', "%{$search}%")
                                            ->orWhere('barcode', 'like', "%{$search}%");
                                    })
                                    ->orderBy('product_name')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Product $product) => [$product->id => "{$product->product_name} ({$product->product_description})" . ($product->barcode ? " - {$product->barcode}" : "")]);
                            })
                            ->reactive()
                            ->searchable()
                            ->placeholder(fn (Forms\Get $get): string =>
                            empty($get('category_id')) ? 'First select category' : 'Search by name or barcode'
                            )
                            ->required()
                            ->native(false)
                            ->disabled(fn (Forms\Get $get): bool => !$get('category_id')),
                        Forms\Components\TextInput::make('cost_price')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('selling_price')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.store_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.category.category_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost Price')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Total')
                        ->numeric()
                        ->using(fn(\Illuminate\Database\Query\Builder $query): float => $query->get()->sum(fn($row) => $row->cost_price * $row->quantity))
                    ),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Selling Price')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Summarizer::make()
                        ->label('Total')
                        ->numeric()
                        ->using(fn(\Illuminate\Database\Query\Builder $query): float => $query->get()->sum(fn($row) => $row->selling_price * $row->quantity))
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(StockImporter::class)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
