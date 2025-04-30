<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Credit;
use App\Models\Customer;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Illuminate\Contracts\Support\Htmlable;

class CustomerCredits extends Page implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;

    protected static string $resource = CustomerResource::class;
    protected static string $view = 'filament.resources.customer-resource.pages.customer-credits';

    public Customer $record;
    public string $customer = '';

    // Define the form model
    public ?array $data = [];

    public function mount(): void
    {
        $this->customer = $this->record->name;
        $this->form->fill([
            'amount' => null,
            'description' => null,
            'type' => 'credit',
        ]);
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->placeholder('Enter amount'),

                Textarea::make('description')
                    ->placeholder('Enter description (optional)'),

                Select::make('type')
                    ->options([
                        'credit' => 'Add Credit',
                        'debit' => 'Retrieve Credit',
                    ])
                    ->default('credit')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Credit::query()->where('customer_id', $this->record->id)
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit' => 'success',
                        'debit' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->money('INR')
                    ->sortable(),
                TextColumn::make('balance')
                    ->money('INR')
                    ->sortable()
                    ->label('Available Balance'),
            ])
            ->poll('5s'); // Poll every 5 seconds for updates
    }

    public function submitCredit(): void
    {
        $data = $this->form->getState();

        // Validate the debit doesn't exceed available balance
        if ($data['type'] === 'debit') {
            $currentBalance = $this->getCurrentBalance();

            if ($data['amount'] > $currentBalance) {
                Notification::make()
                    ->danger()
                    ->title('No Balance')
                    ->body("Customer has {$currentBalance} balance.")
                    ->send();

                return;
            }
        }

        // Calculate new balance
        $currentBalance = $this->getCurrentBalance();
        $newBalance = $data['type'] === 'credit'
            ? $currentBalance + $data['amount']
            : $currentBalance - $data['amount'];

        // Create the credit record
        Credit::create([
            'customer_id' => $this->record->id,
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'balance' => $newBalance,
        ]);

        // Reset form
        $this->form->fill([
            'amount' => null,
            'description' => null,
            'type' => 'credit',
        ]);

        // Force refresh the table
        $this->resetTable();

        // Refresh all widgets - using both standard and component refresh methods
        $this->refreshWidgets();

        // Show success notification
        Notification::make()
            ->success()
            ->title($data['type'] === 'credit' ? 'Credit Added' : 'Credit Retrieved')
            ->send();
    }

    private function getCurrentBalance(): float
    {
        $latestCredit = Credit::where('customer_id', $this->record->id)
            ->latest()
            ->first();

        return $latestCredit ? (float)$latestCredit->balance : 0.00;
    }

    // This is the key method that specifically configures which widgets are used
    protected function getHeaderWidgets(): array
    {
        // This passes the current customer record to the widget
        return [
            CustomerResource\Widgets\CustomerCreditBalanceWidget::make([
                'record' => $this->record,
            ]),
        ];
    }

    /**
     * Refresh all widgets on the page
     */
    public function refreshWidgets(): void
    {
        // Using Filament's built-in widget refresh method
        $this->dispatch('filament.widget-refresh');

        // Also send a custom event for older Filament versions
        $this->dispatch('refresh');
    }
}
