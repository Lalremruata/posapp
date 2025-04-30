<?php

namespace App\Filament\Pages;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCart;
use App\Models\SaleItem;
use App\Models\Stock;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard\Step;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;

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
    public $itemCount = 0;
    public $selectedProductPrice = null;

    public function mount(): void
    {
        $this->form->fill();
        $this->updateTotal();
        $this->updateItemCount();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    protected function updateTotal()
    {
        $this->total = SaleCart::where('user_id', auth()->user()->id)->sum('total_price');
    }

    protected function updateItemCount()
    {
        $this->itemCount = SaleCart::where('user_id', auth()->user()->id)->count();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->searchable()
                    ->autofocus()
                    ->label('Search Product')
                    ->getSearchResultsUsing(fn (string $search): array => Stock::where('store_id', auth()->user()->store_id)
                        ->whereHas('product', function ($query) use ($search) {
                            $query->where('product_name', 'like', "%{$search}%")
                                ->orWhere('barcode', 'like', "%{$search}%");
                        })
                        ->with('product:id,product_name,product_description') // Eager load the product name
                        ->limit(20)
                        ->get()
                        ->pluck('product.product_info', 'product_id')
                        ->toArray()
                    )
                    ->noSearchResultsMessage('No products found.')
                    ->searchPrompt('Search by name or barcode')
                    ->searchingMessage('Searching products...')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(
                        function(callable $set, Get $get){
                            $productId = $get('product_id');
                            $product = Product::find($productId);

                            if($product) {
                                // Set stock ID
                                $stockId = Stock::where('product_id', $productId)
                                    ->where('store_id', auth()->user()->store_id)
                                    ->first();

                                if($stockId) {
                                    $set('stock_id', $stockId->id);
                                }

                                // Set the selling price for display
                                $this->selectedProductPrice = $stockId->selling_price;

                                // Calculate and set the initial total price
                                $quantity = $get('quantity') ?: 1;
                                $discount = $get('discount') ?: 0;
                                $totalPrice = $stockId->selling_price * $quantity;
                                $discountedPrice = $totalPrice - ($totalPrice * ($discount/100));

                                $set('item_total', round($discountedPrice, 2));
                            } else {
                                $this->selectedProductPrice = null;
                                $set('item_total', null);
                            }
                        }
                    ),

                Placeholder::make('product_price')
                    ->label('Unit Price')
                    ->content(function (Get $get) {
                        if ($this->selectedProductPrice !== null) {
                            return '₹ ' . number_format($this->selectedProductPrice, 2);
                        }

                        return 'Select a product to see price';
                    }),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->required()
                    ->live()
                    ->reactive()
                    ->extraAttributes(['ref' => 'productSelect'])
                    ->afterStateUpdated(function(callable $set, Get $get) {
                        if ($this->selectedProductPrice !== null) {
                            $quantity = $get('quantity') ?: 1;
                            $discount = $get('discount') ?: 0;
                            $totalPrice = $this->selectedProductPrice * $quantity;
                            $discountedPrice = $totalPrice - ($totalPrice * ($discount/100));

                            $set('item_total', round($discountedPrice, 2));
                        }
                    })
                    ->minValue(1)
                    ->maxValue(function (Get $get) {
                        $stockId = $get('stock_id');
                        if ($stockId) {
                            $stockQuantity=Stock::where('id',$stockId)
                                ->where('store_id',auth()->user()->store_id)
                                ->pluck('quantity','id')->first();
                            $salesCartQuantity=SaleCart::where('stock_id',$stockId)
                                ->where('store_id', auth()->user()->store_id)
                                ->where('user_id', auth()->user()->id)
                                ->pluck('quantity')->first();
                            $result = $stockQuantity-$salesCartQuantity;
                            return $result;
                        }
                    })
                    ->hint(function(Get $get){
                        $stockId = $get('stock_id');
                        $barcode = $get('barcode');
                        if ($stockId) {
                            $stockQuantity=Stock::where('id',$stockId)
                                ->where('store_id',auth()->user()->store_id)
                                ->pluck('quantity','id')->first();
                            $salesCartQuantity=SaleCart::where('stock_id',$stockId)
                                ->where('store_id', auth()->user()->store_id)
                                ->where('user_id', auth()->user()->id)
                                ->pluck('quantity')->first();
                            $result = $stockQuantity-$salesCartQuantity;
                            if($result)
                                return 'qty. available: '.$result;
                            else
                                return 'stock unavailable';
                        }
                        // elseif()
                        return null;
                    })
                    ->hintColor('danger')
                    ->required(),

                TextInput::make('discount')
                    ->label('Discount (%)')
                    ->default(0)
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function(callable $set, Get $get) {
                        if ($this->selectedProductPrice !== null) {
                            $quantity = $get('quantity') ?: 1;
                            $discount = $get('discount') ?: 0;
                            $totalPrice = $this->selectedProductPrice * $quantity;
                            $discountedPrice = $totalPrice - ($totalPrice * ($discount/100));

                            $set('item_total', round($discountedPrice, 2));
                        }
                    }),

                Placeholder::make('item_total')
                    ->label('Item Total')
                    ->content(function (Get $get) {
                        $itemTotal = $get('item_total');
                        if ($itemTotal !== null) {
                            return '₹ ' . number_format($itemTotal, 2);
                        }

                        return 'Select a product to see total';
                    }),

                Hidden::make('stock_id')
                    ->reactive(),

                Hidden::make('item_total')
                    ->default(0),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SaleCart::query()->where('user_id', auth()->user()->id))
            ->columns([
                TextColumn::make('product.product_name'),
                TextColumn::make('product.product_description')
                    ->label('description'),
                TextColumn::make('quantity'),
                TextColumn::make('cost_price'),
                TextColumn::make('selling_price')
                    ->formatStateUsing(fn ($state) => '₹ ' . number_format($state, 2)),
                TextColumn::make('total_price')
                    ->formatStateUsing(fn ($state) => '₹ ' . number_format($state, 2)),
                TextColumn::make('discount')
                    ->suffix('%'),
            ])
            ->actions([
                DeleteAction::make()
                    ->iconButton()
                    ->icon('heroicon-o-x-circle')
                    ->after(function (){
                        $this->updateTotal();
                        $this->updateItemCount();
                    }),
            ]);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $stock = Stock::find($data['product_id']);
            $cartItem = SaleCart::where('stock_id', $data['stock_id'])
                ->where('user_id', auth()->user()->id)
                ->first();
            $totalPrice = $stock->selling_price * $data['quantity'];
            $discountedPrice = $totalPrice - ($totalPrice * ($data['discount']/100));

            if(!$cartItem) {
                $newData = [
                    'user_id'=> auth()->user()->id,
                    'product_id' => $stock->product_id,
                    'store_id' => auth()->user()->store_id,
                    'stock_id' => $data['stock_id'],
                    'quantity'  => $data['quantity'],
                    'cost_price'=>$stock->cost_price,
                    'selling_price' => $stock->selling_price,
                    'total_price' => $discountedPrice,
                    'discount' => $data['discount'],
                ];
                $data += $newData;
                SaleCart::create($data);
            } else {
                $cartItem->quantity += $data['quantity'];
                $cartItem->selling_price += $discountedPrice;
                $cartItem->discount = $data['discount'];
                $cartItem->update();
            }

            $this->updateTotal();
            $this->updateItemCount();
            $this->selectedProductPrice = null;
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
                if ($data['customer_id']) {
                    // Using existing customer
                    $customer_id = $data['customer_id'];
                } elseif ($data['name']) {
                    // Creating new customer
                    $customer = new Customer;
                    $customer->name = $data['name'];
                    $customer->phone = $data['phone'];
                    $customer->email = $data['email'];
                    $customer->save();
                    $customer_id = Customer::latest()->pluck('id')->first();
                } else {
                    $customer_id = null;
                }

                //Check for Received amount input
                if($data['received_amount'] < $this->total && $customer_id !== null) {
                    $totalAmount = $data['received_amount'];
                    Credit::create([
                        'customer_id' => $customer_id,
                        'amount' => $totalAmount,
                        'type' => 'credit',
                        'balance' => $totalAmount,
                    ]);
                }
                else{
                    $totalAmount = $this->total;
                }

                $cartItems = SaleCart::where('store_id', auth()->user()->store_id)
                    ->where('user_id', auth()->user()->id)
                    ->get();

                //Sales id + 1 for invoice number
                $saleId = Sale::latest()->pluck('id')->first();
                $date = Carbon::now();
                $formattedYear = $date->format('y');
                if ($cartItems->count() > 0) {
                    Sale::create([
                        'store_id' => auth()->user()->store_id,
                        'user_id' => auth()->user()->id,
                        'stock_id' => $cartItems->first()->stock_id,
                        'payment_method' => $data['payment_method'],
                        'sale_date' => Carbon::now(),
                        'customer_id' => $customer_id,
                        'total_amount' => $totalAmount,
                        'invoice_number' => ($saleId + 1).'/'.$formattedYear,
                        'quantity' => $cartItems->sum('quantity'),
                        'transaction_number' => $data['transaction_number'],
                    ]);

                    $saleId = Sale::latest()->pluck('id')->first();
                    foreach ($cartItems as $item) {
                        $stock = Stock::where('id', $item->stock_id)
                            ->first();
                        $stock->quantity -= $item->quantity;
                        $stock->update();
                        SaleItem::create([
                            'sale_id' => $saleId,
                            'product_id' => $item->product_id, //Product Id
                            'quantity' => $item->quantity,
                            'cost_price' => $item->cost_price,
                            'selling_price' => $item->selling_price,
                            'discount' => $item->discount,
                            'total_price' => $totalAmount,
                            'sale_date' => Carbon::now(),
                        ]);
                        $item->delete();
                    }
                    $this->updateTotal();
                    $this->updateItemCount();
                    $this->selectedProductPrice = null;
                }
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
                            Select::make('customer_id')
                                ->label('Select Existing Customer')
                                ->searchable()
                                ->options(function () {
                                    return Customer::query()
                                        ->pluck('name', 'id');
                                })
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state) {
                                        $customer = Customer::find($state);
                                        $set('name', $customer->name);
                                        $set('phone', $customer->phone);
                                        $set('email', $customer->email);
                                    } else {
                                        $set('name', null);
                                        $set('phone', null);
                                        $set('email', null);
                                    }
                                }),
                            TextInput::make('name')
                                ->label('Customer Name (New)')
                                ->helperText('Fill these fields only if creating a new customer'),
                            TextInput::make('phone')
                                ->label('Contact')
                                ->numeric(),
                            TextInput::make('email')
                                ->label('Email')
                                ->email(),
                        ])
                    ])
            ]);
    }

    public function getCheckoutProperty()
    {
        if ($this->itemCount > 0) {
            return $this->checkoutAction();
        }

        return new HtmlString('');
    }
}
