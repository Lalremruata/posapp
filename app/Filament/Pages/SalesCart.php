<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCart;
use App\Models\SaleItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Query\Builder;


class SalesCart extends Page implements HasForms, HasTable, HasActions
{
    protected static ?string $model = SaleCart::class;
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Sales Cart';
    protected static ?string $navigationGroup = 'Sales';

    protected static string $view = 'filament.pages.sales';
    public ?array $data = [];
    public $total;

    public function mount(): void
    {
        $this->form->fill();
        $this->updateTotal();
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }
    protected function updateTotal()
    {
        $this->total = SaleCart::where('user_id', auth()->user()->id)->sum('selling_price');
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->searchable()
                    ->autofocus()
                    ->label('Search Product')
                    ->getSearchResultsUsing(fn (string $search): array => Product::where('product_name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->limit(20)->pluck('product_name', 'id')->toArray())
                    ->noSearchResultsMessage('No products found.')
                    ->searchPrompt('Search by name or barcode')
                    ->searchingMessage('Searching products...')
                    ->required()
                    ->native(false)
                    ->extraAttributes(['ref' => 'productSelect']),
                TextInput::make('quantity')
                    ->label('quantity')
                    ->numeric()
                    ->default(1)
                    ->required(),
            ])->columns(2)
            ->statePath('data');
    }
    public function table(Table $table): Table
    {
        return $table
        ->query(SaleCart::query()->where('user_id', auth()->user()->id))
            ->columns([
                    TextColumn::make('product.product_name'),
                    TextColumn::make('quantity'),
                    TextColumn::make('selling_price')
                    ->label('Price'),
                    TextColumn::make('discount'),
            ])
            ->actions([
                DeleteAction::make()
                ->iconButton()
                ->icon('heroicon-o-x-circle')
                ->after(function (){
                    $this->updateTotal();
                }),
            ]);
    }
    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $product = Product::find($data['product_id']);
            $data = [
                'user_id'=> auth()->user()->id,
                'product_id' => $product->id,
                'quantity'  => $data['quantity'],
                'cost_price'=>$product->cost_price,
                'selling_price' => $product->selling_price,
                'discount' =>0,
            ];
            SaleCart::create($data);
            $this->updateTotal();
            $this->form->fill();
            $this->dispatch('formSaved');
        } catch (Halt $exception) {
            //throw $th;
        }
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
    public function checkoutAction(): Action
    {
           return Action::make('checkout')
           ->button()
           ->icon('heroicon-o-bolt')
           ->color('warning')
           ->action(function (array $data) {
            // $totalAmount = SaleCart::where('user_id', auth()->user()->id)->sum('selling_price');

            if($data['name'])
            {
                $customer = new Customer;
                $customer->name = $data['name'];
                $customer->phone = $data['phone'];
                $customer->email = $data['email'];
                $customer->save();
                $customer_id = Customer::latest()->pluck('id')->first();
            }
            else{
                $customer_id = null;
            }
            $cartItems = SaleCart::where('user_id',auth()->user()->id)->get();
            Sale::create([
                'user_id' => auth()->user()->id,
                'customer_id' => $customer_id,
                'total_amount' => $this->total,
                'quantity' => $cartItems->sum('quantity'),
                'payment_method' => $data['payment_method'],
                'sale_date' => Carbon::now(),
                'transaction_number' => $data['transaction_number'],
            ]);

            $saleId = Sale::latest()->pluck('id')->first();
            foreach ($cartItems as $item) {
                $product = Product::where('id', $item->product_id)
                ->first();
                $product->quantity_in_stock -= $item->quantity;
                $product->update();
                SaleItem::create([
                    'sale_id' => $saleId,
                    'product_id' => $item->product_id, //Product Id
                    'quantity' => $item->quantity,
                    'price' => $item->selling_price,
                ]);
                $item->delete();
            }
            $this->updateTotal();
        })
            ->steps([
                Step::make('Payment')
                ->schema([
                    Section::make([
                        TextInput::make('received_amount')
                        ->autofocus(true)
                            ->prefix('₹')
                            ->numeric()
                            ->required()
                            ->default(function(){
                                return $this->total;
                            }),
                            Select::make('payment_method')
                            ->options([
                                "cash" => "cash",
                                "upi" => "upi",
                                "bank transfer"=>"bank transfer",
                                "cheque" => "cheque"
                            ])
                            ->required(),
                            TextInput::make('transaction_number')
                    ])
                            ]),
                Step::make('Customer Details')
                ->schema([
                    Section::make([
                        TextInput::make('name')
                            ->autofocus()
                            ->label('customer name'),
                        TextInput::make('phone')
                            ->label('Contact')
                            ->numeric(),
                        TextInput::make('email')
                            ->label('email')
                            ->email(),
                    ])
                ])
                    ]);

    }
}
