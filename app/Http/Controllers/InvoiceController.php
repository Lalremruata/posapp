<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use LaravelDaily\Invoices\Invoice;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;

class InvoiceController extends Controller
{
    public function downloadInvoice(Request $request, Sale $sale)
    {

//        $date = Carbon::now();
//        $formattedYear = $date->format('y');
        $salesItem = SaleItem::where('sale_id', $sale->id)->get();
        $client = new Party([
            'name'          => $sale->store->store_name,
            'phone'         => $sale->store->phone_number,
            'custom_fields' => [
                'Address'        => $sale->store->location,
            ],
        ]);
        $customer = new Party([
            'name'          => optional($sale->customer)->name,
            'phone'       => optional($sale->customer)->phone,
            'custom_fields' => [
                'Bill number' =>  $sale->invoice_number,
            ],
        ]);
        $notes = [
            'your multiline',
            'additional notes',
            'in regards of delivery or something else',
        ];
        $notes = implode("<br>", $notes);

        $salesitem = $salesItem->map(function ($salesItem) {
            return Invoice::makeItem($salesItem->product->product_name)
                ->title($salesItem->product->product_name.' '.$salesItem->product->product_description)
                ->pricePerUnit($salesItem->selling_price)
                ->quantity($salesItem->quantity)
                ->subTotalPrice($salesItem->total_price);
        })->toArray();

        $invoice = Invoice::make('receipt')
            ->seller($client)
            ->buyer($customer)
            ->serialNumberFormat('{SEQUENCE}/{SERIES}')
            ->dateFormat('d/m/Y')
            ->currencySymbol('₹')
            ->currencyCode('Rupees')
            ->currencyFraction('paise')
            ->currencyFormat('{SYMBOL}{VALUE}')
            ->currencyThousandsSeparator(',')
            ->addItems($salesitem)
//            ->series($formattedYear)
            ->sequence($sale->id)
            ->date(Carbon::parse($sale->sale_date))
            ->delimiter('/')
            ->payUntilDays(0)
            // ->logo(public_path('/images/bcm-logo.svg'))
            ;

        return $invoice->download();
    }
}
