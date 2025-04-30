<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Store;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\View\View;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
//            Actions\CreateAction::make(),
//            Actions\Action::make('View All Sales')
//                ->icon('heroicon-o-arrow-right')
//            ->url(SaleResource::getUrl('all-sales'))
                // ->iconButton()
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
