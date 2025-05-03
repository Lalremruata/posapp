<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Discount Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->required()
                            ->default('percentage'),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get): string => $get('type') === 'percentage' ? '%' : '₹')
                            ->helperText(fn (Forms\Get $get): string =>
                            $get('type') === 'percentage'
                                ? 'Enter a percentage value (e.g., 10 for 10%)'
                                : 'Enter a fixed amount in rupees'),

                        Forms\Components\DateTimePicker::make('start_date')
                            ->helperText('Leave empty for immediate start'),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->helperText('Leave empty for no expiration')
                            ->after('start_date'),

                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Discount Rules')
                    ->schema([
                        Forms\Components\TextInput::make('min_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->helperText('Minimum quantity of product required for discount'),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Higher priority discounts are applied first'),

                        Forms\Components\TextInput::make('max_uses')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited uses'),

                        Forms\Components\TextInput::make('used_count')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Number of times this discount has been used'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Apply Discount To')
                    ->schema([
                        Forms\Components\Select::make('products')
                            ->relationship('products', 'product_name')
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Forms\Components\Select::make('categories')
                            ->relationship('categories', 'category_name')
                            ->multiple()
                            ->preload()
                            ->searchable(),

                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string =>
                    match($state) {
                        'percentage' => 'primary',
                        'fixed' => 'success',
                        default => 'gray',
                    }
                    ),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn (string $state, Discount $record): string =>
                    $record->type === 'percentage'
                        ? "{$state}%"
                        : "₹{$state}"
                    ),

                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('used_count')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (string $state, Discount $record): string =>
                    $record->max_uses
                        ? "{$state}/{$record->max_uses}"
                        : $state
                    ),

                // Status badge
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(function (Discount $record): string {
                        $now = Carbon::now();

                        if (!$record->is_active) {
                            return 'Inactive';
                        }

                        if ($record->start_date && $now->lt($record->start_date)) {
                            return 'Scheduled';
                        }

                        if ($record->end_date && $now->gt($record->end_date)) {
                            return 'Expired';
                        }

                        if ($record->max_uses !== null && $record->used_count >= $record->max_uses) {
                            return 'Used Up';
                        }

                        return 'Active';
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match($state) {
                            'Active' => 'success',
                            'Scheduled' => 'info',
                            'Expired' => 'danger',
                            'Used Up' => 'warning',
                            'Inactive' => 'gray',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'scheduled' => 'Scheduled',
                        'expired' => 'Expired',
                        'used_up' => 'Used Up',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }

                        $now = Carbon::now();

                        return match($data['value']) {
                            'active' => $query->where('is_active', true)
                                ->where(function (Builder $query) use ($now) {
                                    $query->whereNull('start_date')
                                        ->orWhere('start_date', '<=', $now);
                                })
                                ->where(function (Builder $query) use ($now) {
                                    $query->whereNull('end_date')
                                        ->orWhere('end_date', '>=', $now);
                                })
                                ->where(function (Builder $query) {
                                    $query->whereNull('max_uses')
                                        ->orWhereRaw('used_count < max_uses');
                                }),
                            'inactive' => $query->where('is_active', false),
                            'scheduled' => $query->where('is_active', true)
                                ->whereNotNull('start_date')
                                ->where('start_date', '>', $now),
                            'expired' => $query->where('is_active', true)
                                ->whereNotNull('end_date')
                                ->where('end_date', '<', $now),
                            'used_up' => $query->where('is_active', true)
                                ->whereNotNull('max_uses')
                                ->whereRaw('used_count >= max_uses'),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query): Builder =>
                    $query->whereHas('products')
                    ),

                Tables\Filters\Filter::make('has_categories')
                    ->label('Has Categories')
                    ->query(fn (Builder $query): Builder =>
                    $query->whereHas('categories')
                    ),

                Tables\Filters\Filter::make('has_customers')
                    ->label('Has Customers')
                    ->query(fn (Builder $query): Builder =>
                    $query->whereHas('customers')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                // Quick toggle action
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Discount $record): string =>
                    $record->is_active ? 'Deactivate' : 'Activate'
                    )
                    ->icon(fn (Discount $record): string =>
                    $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'
                    )
                    ->color(fn (Discount $record): string =>
                    $record->is_active ? 'danger' : 'success'
                    )
                    ->action(function (Discount $record): void {
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),

                // Duplicate action
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Discount $record): void {
                        $newDiscount = $record->replicate();
                        $newDiscount->name = "Copy of {$record->name}";
                        $newDiscount->used_count = 0;
                        $newDiscount->save();

                        // Copy relationships
                        $newDiscount->products()->attach($record->products()->pluck('product_id'));
                        $newDiscount->categories()->attach($record->categories()->pluck('product_category_id'));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // Bulk activate
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = true;
                                $record->save();
                            }
                        }),

                    // Bulk deactivate
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = false;
                                $record->save();
                            }
                        }),
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
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
