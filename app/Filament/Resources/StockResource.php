<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\Product;
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

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Manage Stocks';
    public static function getEloquentQuery(): Builder
    {
        if(auth()->user()->roles() == 'Admin') {
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
                Forms\Components\Select::make('store_id')
                    ->label('Select Store')
                    ->options(function(){
                        if(auth()->user()->roles()== 'Admin')
                        Store::all()->pluck('store_name', 'id');
                    else
                        return Store::where('id',auth()->user()->store_id)->pluck('store_name', 'id');
                    
                        
                 })
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->label('Select Product')
                    ->searchable()
                    ->relationship(
                        name: 'product',
                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('product_name')->orderBy('product_description'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->product_name} ({$record->product_description})")
                    ->searchable(['product_name', 'barcode'])
                    // ->getSearchResultsUsing(fn (string $search): array => Product::where('product_name', 'like', "%{$search}%")
                    //     ->orWhere('barcode', 'like', "%{$search}%")
                    //     ->limit(20)->pluck('product_name', 'id')->toArray())
                    // ->getOptionLabelsUsing(fn (array $values): array => Product::whereIn('product_description', $values)->pluck('product_name', 'id')->toArray())                    ->noSearchResultsMessage('No products found.')
                    ->searchPrompt('Search by name or barcode')
                    ->searchingMessage('Searching products...')
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
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
            ])
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
