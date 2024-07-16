<?php

namespace App\Filament\Resources\StockResource\Pages;

use App\Filament\Resources\StockResource;
use App\Models\Store;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        if(auth()->user()->store_id=='1') {
            $stores = Store::all();
            $tabs=[null => ListRecords\Tab::make('All'),];
            foreach ($stores as $store) {
                $tabs[$store->store_name] = ListRecords\Tab::make()
            ->query(fn ($query) => $query->where('store_id', $store->id));
            }
            return $tabs;
        }
        else {
        return [
            //return nothing
        ];
        }

    }
}
