<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Models\Stock;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
class CreateStock extends CreateRecord
{
    protected static string $resource = StockResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function handleRecordCreation(array $data): Model
    {
        // Runs after the form fields are saved to the database.
        $stock = Stock::where('product_id',$this->data['product_id'])
                        ->where('store_id',$this->data['store_id'])->first();
        if($stock)
        {
            $stock->quantity += $this->data['quantity'];
            $stock->update();
            return $stock;
        }
        else
            return static::getModel()::create($data);

    }
}
