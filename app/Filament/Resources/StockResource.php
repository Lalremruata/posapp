<?php

namespace App\Filament\Resources;

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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component as Livewire;

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
                            ->reactive(),
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
                            ->reactive()
                            ->searchable()
                            ->placeholder(fn (Forms\Get $get): string =>
                            empty($get('category_id')) ? 'First select category' : 'Select a product'
                            )
                            ->required()
                            ->native(false)
                            ->disabled(fn (Forms\Get $get): bool => !$get('category_id')),
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
