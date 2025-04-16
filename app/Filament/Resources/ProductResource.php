<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Forms\Components\BarcodeGenerator;
use App\Models\Product;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Manage Products';
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->roles->first()->name == 'Admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns([
                        'sm' => 2,
                        'xl' => 2,
                        '2xl' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->autofocus()
                            ->label('Category')
                            ->options(ProductCategory::all()->pluck('category_name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'supplier_name')
                            ->searchable(),
                    ]),
                Forms\Components\Section::make()
                ->columns([
                    'sm' => 2,
                    'xl' => 2,
                    '2xl' => 2,
                ])
                ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('product_description')
                    ->required()
                    ->maxLength(200),
                    Forms\Components\TextInput::make('cost_price')
                        ->required()
                        ->numeric(),
                Forms\Components\TextInput::make('selling_price')
                    ->required()
                    ->numeric(),
                BarcodeGenerator::make('barcode')
                ->helperText('Click the button to generate barcode.'),
                // Forms\Components\TextInput::make('barcode')
                //     ->label('Barcode')
                //     ->reactive(),
                // Forms\Components\ToggleButtons::make('barcode_generator')
                // ->label('')
                //     ->options([
                //         'generate-barcode' => 'Generate Barcode',
                //     ])
                //     ->icons([
                //         'generate-barcode' => 'heroicon-o-check-badge',
                //     ])
                //     ->colors([
                //         'generate-barcode'=> 'warning',
                //     ])
                //     ->afterStateUpdated(function($set){
                //         $set('barcode', str_pad((string)rand(0, 99999999), 9, '0', STR_PAD_LEFT));
                //     })
                //     ->extraAttributes([
                //         'class' => 'pt-6 bg-gray-500 tex-red-500',
                //     ])
                //     ->dehydrated(false)
                //     ->reactive(),

                    // ...
                ])


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product_description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')
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
                Tables\Actions\Action::make('barCode')
                ->url(function (Product $record) {
                   return static::getUrl('barCode',['record'=>$record]);

                }),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'barCode' => Pages\GeneratedBarCode::route('/{record}/barcode'),
        ];
    }
}
