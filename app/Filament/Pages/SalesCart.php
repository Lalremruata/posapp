<?php

namespace App\Filament\Pages;

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
                    // ->relationship(
                    //     name: 'stock', // The name of the relationship in SalesCart model
                    //     modifyQueryUsing: fn (Builder $query) => $query
                    //         ->whereHas('product', function (Builder $query) {
                    //             $query->whereHas('stocks', function (Builder $query) {
                    //                 $query->where('store_id', auth()->user()->store_id);
                    //             });
                    //         })
                    //         ->with('product')
                    //         ->orderBy('product.product_name')
                    //         ->orderBy('product.product_description')
                    // )
                    // ->getOptionLabelFromRecordUsing(fn (Stock $record) => $record->product
                    //     ? "{$record->product->product_name} ({$record->product->product_description})"
                    //     : 'Unknown Product'
                    // )
                    // ->searchable(['product.product_name', 'product.barcode'])
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
                    // ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->getConcatenatedName())
                    ->noSearchResultsMessage('No products found.')
                    ->searchPrompt('Search by name or barcode')
                    ->searchingMessage('Searching products...')
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(
                        function(callable $set,Get $get){
                            $productId = $get('product_id');
                            $stockId = Stock::where('id', $productId)
                                ->first();
                                if($stockId)
                                {
                                    $set('stock_id', $stockId->id);
                                }
                        }
                        ),
                TextInput::make('quantity')
                    ->label('quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->extraAttributes(['ref' => 'productSelect']),
                TextInput::make('discount')
                ->label('discount')
                ->default(0)
                ->numeric(),
                Hidden::make('stock_id')
                ->reactive()
            ])->columns(2)
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
                    TextColumn::make('selling_price')
                    ->label('Price'),
                    TextColumn::make('discount')
                    ->suffix('%'),
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
            $cartItem = SaleCart::where('stock_id', $data['stock_id'])->first();
            $totalPrice = $product->selling_price * $data['quantity'];
            if(!$cartItem)
            {

                $newData = [
                    'user_id'=> auth()->user()->id,
                    'product_id' => $product->id,
                    'store_id' => auth()->user()->store_id,
                    'stock_id' => $data['stock_id'],
                    'quantity'  => $data['quantity'],
                    'cost_price'=>$product->cost_price,
                    'selling_price' => $totalPrice - ($totalPrice * ($data['discount']/100)),
                    'discount' =>$data['discount'],
                ];
                $data += $newData;
                SaleCart::create($data);
            }
            else{
                // $stock=Stock::where('id', $data['stock_id'])->first();
                // $user_id= auth()->user()->id;
                // $product_id => $product->id,
                // 'store_id' => auth()->user()->store_id,
                // 'stock_id' => $data['stock_id'],
                $cartItem->quantity += $data['quantity'];
                // 'cost_price'=>$product->cost_price,
                $cartItem->selling_price += $totalPrice - ($totalPrice * ($data['discount']/100));
                $cartItem->discount = $data['discount'];
                $cartItem->update();
            }
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
            $cartItems = SaleCart::where('store_id',auth()->user()->store_id)->get();
            Sale::create([
                'store_id' => auth()->user()->store_id,
                'user_id' => auth()->user()->id,
                'stock_id' => $cartItems->first()->stock_id,
                'payment_method' => $data['payment_method'],
                'sale_date' => Carbon::now(),
                'customer_id' => $customer_id,
                'total_amount' => $this->total,
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
                    'price' => $item->selling_price,
                    'sale_date' => Carbon::now(),
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
