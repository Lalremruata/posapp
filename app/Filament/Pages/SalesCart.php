<?php

namespace App\Filament\Pages;

use App\Models\AppliedDiscount;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Discount;
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
use App\Services\DiscountService;

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
    public $barcodeInput = '';
    protected ?DiscountService $discountService = null;


    public function mount(): void
    {
        $this->form->fill();
        $this->updateTotal();
        $this->updateItemCount();
    }

    protected function getDiscountService(): DiscountService
    {
        if (!$this->discountService) {
            $this->discountService = new DiscountService();
        }

        return $this->discountService;
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
                // Add a dedicated barcode input field
                TextInput::make('barcode')
                    ->hidden('barcode')
                    ->label('Scan Barcode')
                    ->placeholder('Scan or enter barcode')
                    ->autofocus()
                    ->extraAttributes([
                        'autocomplete' => 'off',
                        'class' => 'barcode-input',
                    ])
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (empty($state)) {
                            return;
                        }

                        // Find product by barcode
                        $product = Product::where('barcode', $state)->first();

                        if (!$product) {
                            // Product not found
                            // You might want to show a notification here
                            $this->dispatch('filament-notifications.error', [
                                'message' => 'Product not found!',
                                'duration' => 3000,
                            ]);
                            $set('barcode', ''); // Clear the barcode input
                            return;
                        }

                        // Get stock information
                        $stock = Stock::where('product_id', $product->id)
                            ->where('store_id', auth()->user()->store_id)
                            ->first();

                        if (!$stock || $stock->quantity <= 0) {
                            // No stock available
                            $this->dispatch('filament-notifications.error', [
                                'message' => 'Product out of stock!',
                                'duration' => 3000,
                            ]);
                            $set('barcode', ''); // Clear the barcode input
                            return;
                        }

                        // Set form data for automatic processing
                        $set('product_id', $product->id);
                        $set('stock_id', $stock->id);
                        $set('quantity', 1);
                        $set('discount', 0);
                        $this->selectedProductPrice = $stock->selling_price;
                        $set('item_total', $stock->selling_price);

                        // Automatically add to cart
                        $this->processScannedItem([
                            'product_id' => $product->id,
                            'stock_id' => $stock->id,
                            'quantity' => 1,
                            'discount' => 0,
                            'item_total' => $stock->selling_price
                        ]);

                        // Clear the barcode input for next scan
                        $set('barcode', '');
                    })
                    ->extraAttributes([
                        'x-data' => '',
                        'x-init' => '$nextTick(() => { $el.focus(); })',
                        '@keydown.window' => '$nextTick(() => { $el.focus(); })',
                    ]),

                Select::make('product_id')
                    ->hidden('product_id')
                    ->searchable()
                    ->label('Search Product (Manual)')
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
                    ->hidden('product_id')
                    ->label('Unit Price')
                    ->content(function (Get $get) {
                        if ($this->selectedProductPrice !== null) {
                            return '₹ ' . number_format($this->selectedProductPrice, 2);
                        }

                        return 'Select a product to see price';
                    }),

                TextInput::make('quantity')
                    ->hidden('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->live()
                    ->reactive()
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
                        return null;
                    })
                    ->hintColor('danger'),

                TextInput::make('discount')
                    ->hidden()
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
                    ->hidden('item_total')
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

    // New method to process scanned items
    public function processScannedItem(array $data): void
    {
        try {
            $stock = Stock::find($data['stock_id']);

            if (!$stock) {
                $this->dispatch('filament-notifications.error', [
                    'message' => 'Stock not found!',
                    'duration' => 3000,
                ]);
                return;
            }

            $product = Product::find($stock->product_id);
            $quantity = $data['quantity'] ?? 1;

            // Calculate the base price
            $basePrice = $stock->selling_price * $quantity;

            if (!$this->discountService) {
                $this->discountService = new DiscountService();
            }
            // Check for applicable discounts
            $discountResult = $this->discountService->calculateBestDiscount(
                $product,
                $basePrice,
                $quantity
            );

            // Get discount percentage (for display)
            $discountPercentage = 0;
            if ($discountResult['discount'] && $discountResult['discount']->type === 'percentage') {
                $discountPercentage = $discountResult['discount']->value;
            } elseif ($discountResult['discount'] && $basePrice > 0) {
                // Calculate equivalent percentage for fixed discounts
                $discountPercentage = round(($discountResult['discount_amount'] / $basePrice) * 100, 2);
            }

            // Final price after automatic discount
            $finalPrice = $discountResult['discounted_price'];

            // Apply manual discount if provided
            $manualDiscountPercentage = $data['discount'] ?? 0;
            if ($manualDiscountPercentage > 0) {
                $manualDiscountAmount = $finalPrice * ($manualDiscountPercentage / 100);
                $finalPrice = $finalPrice - $manualDiscountAmount;

                // Combine discounts for display
                $discountPercentage = $discountPercentage + $manualDiscountPercentage;
            }

            // Round to 2 decimal places
            $finalPrice = round($finalPrice, 2);

            // Check if item already exists in cart
            $cartItem = SaleCart::where('stock_id', $data['stock_id'])
                ->where('user_id', auth()->user()->id)
                ->first();

            if(!$cartItem) {
                // Create new cart item
                $newData = [
                    'user_id'=> auth()->user()->id,
                    'product_id' => $stock->product_id,
                    'store_id' => auth()->user()->store_id,
                    'stock_id' => $data['stock_id'],
                    'quantity'  => $quantity,
                    'cost_price'=> $stock->cost_price,
                    'selling_price' => $stock->selling_price,
                    'total_price' => $finalPrice,
                    'discount' => $discountPercentage,
                    'discount_source' => $discountResult['discount'] ? 'automatic' : 'manual',
                    'discount_id' => $discountResult['discount'] ? $discountResult['discount']->id : null,
                ];

                $cartItem = SaleCart::create($newData);

                // Get product name for notification
                $productName = $product->product_name ?? 'Product';

                // Include discount info in the notification
                $discountInfo = $discountPercentage > 0 ? " with {$discountPercentage}% discount" : "";

                // Send success notification
                $this->dispatch('filament-notifications.success', [
                    'message' => "Product added to cart: {$productName}{$discountInfo}",
                    'duration' => 3000,
                ]);

                // Also dispatch to our notify event for the UI
                $this->dispatch('notify', [
                    'message' => 'Product added to cart',
                    'product' => $productName,
                    'quantity' => $quantity,
                    'discount' => $discountPercentage,
                ]);
            } else {
                // Update existing cart item
                $newQuantity = $cartItem->quantity + $quantity;

                // Recalculate with the new quantity
                $newBasePrice = $stock->selling_price * $newQuantity;
                $newDiscountResult = $this->discountService->calculateBestDiscount(
                    $product,
                    $newBasePrice,
                    $newQuantity
                );

                // Get updated discount percentage
                $newDiscountPercentage = 0;
                if ($newDiscountResult['discount'] && $newDiscountResult['discount']->type === 'percentage') {
                    $newDiscountPercentage = $newDiscountResult['discount']->value;
                } elseif ($newDiscountResult['discount'] && $newBasePrice > 0) {
                    $newDiscountPercentage = round(($newDiscountResult['discount_amount'] / $newBasePrice) * 100, 2);
                }

                $newFinalPrice = $newDiscountResult['discounted_price'];

                // Apply manual discount if provided
                if ($manualDiscountPercentage > 0) {
                    $newManualDiscountAmount = $newFinalPrice * ($manualDiscountPercentage / 100);
                    $newFinalPrice = $newFinalPrice - $newManualDiscountAmount;

                    // Combine discounts for display
                    $newDiscountPercentage = $newDiscountPercentage + $manualDiscountPercentage;
                }

                // Update the cart item
                $cartItem->quantity = $newQuantity;
                $cartItem->total_price = round($newFinalPrice, 2);
                $cartItem->discount = $newDiscountPercentage;
                $cartItem->discount_source = $newDiscountResult['discount'] ? 'automatic' : 'manual';
                $cartItem->discount_id = $newDiscountResult['discount'] ? $newDiscountResult['discount']->id : null;
                $cartItem->update();

                // Get product name for notification
                $productName = $product->product_name ?? 'Product';

                // Include discount info in the notification
                $discountInfo = $newDiscountPercentage > 0 ? " with {$newDiscountPercentage}% discount" : "";

                // Send success notification
                $this->dispatch('filament-notifications.success', [
                    'message' => "Product quantity updated: {$productName}{$discountInfo}",
                    'duration' => 3000,
                ]);

                // Also dispatch to our notify event for the UI
                $this->dispatch('notify', [
                    'message' => 'Product quantity updated',
                    'product' => $productName,
                    'quantity' => $cartItem->quantity,
                    'discount' => $newDiscountPercentage,
                ]);
            }

            $this->updateTotal();
            $this->updateItemCount();
            $this->selectedProductPrice = null;
            $this->form->fill();
            $this->dispatch('formSaved');

        } catch (Halt $exception) {
            // Handle exception
            $this->dispatch('filament-notifications.error', [
                'message' => 'Error processing item: ' . $exception->getMessage(),
                'duration' => 3000,
            ]);
        }
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
            $this->processScannedItem($data);
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
                // Handle customer assignment or creation
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

                // Get cart items
                $cartItems = SaleCart::where('store_id', auth()->user()->store_id)
                    ->where('user_id', auth()->user()->id)
                    ->get();

                // Only proceed if there are items in the cart
                if ($cartItems->count() > 0) {
                    // Calculate the ACTUAL total value of goods sold
                    $actualSaleValue = $this->total;

                    // Get the amount received from customer
                    $receivedAmount = isset($data['received_amount']) && $data['received_amount'] > 0
                        ? $data['received_amount']
                        : 0;

                    // Generate invoice number
                    $saleId = Sale::latest()->pluck('id')->first() ?? 0;
                    $date = Carbon::now();
                    $formattedYear = $date->format('y');
                    $invoiceNumber = ($saleId + 1).'/'.$formattedYear;

                    // Determine payment status
                    $paymentStatus = 'paid';
                    if ($receivedAmount <= 0) {
                        $paymentStatus = 'unpaid';
                    } elseif ($receivedAmount < $actualSaleValue) {
                        $paymentStatus = 'partial';
                    }

                    // Create the sale record with ACCURATE total value and payment tracking
                    $sale = Sale::create([
                        'store_id' => auth()->user()->store_id,
                        'user_id' => auth()->user()->id,
                        'stock_id' => $cartItems->first()->stock_id,
                        'payment_method' => $data['payment_method'],
                        'sale_date' => Carbon::now(),
                        'customer_id' => $customer_id,
                        'total_amount' => $actualSaleValue, // This is the ACTUAL total value of the sale
                        'amount_paid' => $receivedAmount,   // This is what was actually paid
                        'payment_status' => $paymentStatus, // Indicates if fully paid or partial
                        'invoice_number' => $invoiceNumber,
                        'quantity' => $cartItems->sum('quantity'),
                        'transaction_number' => $data['transaction_number'] ?? null,
                    ]);

                    // Get the created sale ID
                    $saleId = $sale->id;

                    // Record sale items
                    foreach ($cartItems as $item) {
                        // Update inventory
                        $stock = Stock::where('id', $item->stock_id)->first();
                        $stock->quantity -= $item->quantity;
                        $stock->update();

                        // Create sale item record
                        SaleItem::create([
                            'sale_id' => $saleId,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'cost_price' => $item->cost_price,
                            'selling_price' => $item->selling_price,
                            'discount' => $item->discount,
                             'discount_id' => $item->discount_id,
                             'discount_type' => $item->discount_source ?? 'manual',
                            'unit_price' => $item->unit_price,
                            'discount_amount' => $item->discount_amount,
                            'line_total' => $item->line_total,
                        'sub_total' => $item->total_price, // Per-item total
                            'sale_date' => Carbon::now(),
                        ]);

                        // Clear cart item
                        $item->delete();
                    }

                    // Handle credit for partial or unpaid sales
                    if (($paymentStatus === 'partial' || $paymentStatus === 'unpaid') && $customer_id !== null) {
                        // Calculate credit amount (what's not paid)
                        $creditAmount = $actualSaleValue - $receivedAmount;

                        // Create credit record using your existing ledger approach
                        Credit::create([
                            'customer_id' => $customer_id,
                            'sale_id' => $saleId, // Link to the specific sale
                            'amount' => $creditAmount,
                            'description' => "Credit for Invoice #{$invoiceNumber}",
                            'type' => 'credit', // This is a debt the customer owes (credit in accounting terms)
                            'balance' => $creditAmount,
                            'payment_method' => $data['payment_method'],
                            'transaction_number' => $data['transaction_number'] ?? null,
                            'status' => 'active' // New status field from our hybrid approach
                        ]);

                        // Display partial payment message
                        $this->dispatch('filament-notifications.info', [
                            'message' => "Sale completed with credit: ₹{$creditAmount} for customer",
                            'duration' => 5000,
                        ]);
                    } else if (($paymentStatus === 'partial' || $paymentStatus === 'unpaid') && $customer_id === null) {
                        // Cannot have credit without customer
                        $this->dispatch('filament-notifications.error', [
                            'message' => 'Customer information is required for partial payments',
                            'duration' => 5000,
                        ]);

                        // Revert the sale creation
                        $sale->delete();
                        return;
                    }

                    // Update UI components
                    $this->updateTotal();
                    $this->updateItemCount();
                    $this->selectedProductPrice = null;

                    // Success notification
                    $this->dispatch('filament-notifications.success', [
                        'message' => 'Sale completed successfully! Invoice: ' . $invoiceNumber,
                        'duration' => 5000,
                    ]);
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
