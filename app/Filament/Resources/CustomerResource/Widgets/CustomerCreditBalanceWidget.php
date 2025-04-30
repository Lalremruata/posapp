<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Credit;
use App\Models\Customer;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditBalanceWidget extends Widget
{
    protected static string $view = 'filament.resources.customer-resource.widgets.customer-credit-balance-widget';
    // Make the widget refreshable
    protected static bool $isLazy = false;

    // Add polling to automatically refresh the widget
    protected static ?string $pollingInterval = '5s';

    // This is the record property that we need to make sure is set properly
    public ?Model $record = null;

    // Make widget regenerate when needed
    protected function getViewData(): array
    {
        // Pass the calculated balance to the view
        return [
            'balance' => $this->getBalance(),
        ];
    }

    /**
     * Get the current balance for this customer
     */
    public function getBalance(): float
    {
        // If no record is available, return zero
        if (!$this->record || !($this->record instanceof Customer)) {
            return 0.00;
        }

        // Get the latest credit record for this customer
        $latestCredit = Credit::where('customer_id', $this->record->id)
            ->latest('created_at')  // Make sure we use created_at for sorting
            ->first();

        // Return the balance or zero if no credit records exist
        return $latestCredit ? (float)$latestCredit->balance : 0.00;
    }

    /**
     * Make the widget accept parameters
     */
//    public static function make(array $parameters = []): static
//    {
//        return app(static::class, $parameters);
//    }
}
