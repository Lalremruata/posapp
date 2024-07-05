<?php

namespace App\Filament\Pages;

use App\Models\SaleCart;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Query\Builder;


class Sales extends Page implements HasForms, HasTable, HasActions
{
    protected static ?string $model = SaleCart::class;
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string $view = 'filament.pages.sales';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->required(),
                MarkdownEditor::make('content'),
                // ...
            ])
            ->statePath('data');
    }
    public function table(Table $table): Table
    {
        return $table
        ->query(SaleCart::query()->where('user_id', auth()->user()->id))
            ->columns([
                    TextColumn::make('product.product_name'),
                    TextColumn::make('quantity'),
                    TextColumn::make('product.cost_price'),
                    TextColumn::make('product.selling_price'),
                    TextColumn::make('discount'),
            ]);
    }
    public function create(): void
    {
        dd($this->form->getState());
    }
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Add to cart'))
                ->submit('save')
                ->color('warning')
                ->icon('heroicon-o-shopping-cart'),
        ];
    }
}
