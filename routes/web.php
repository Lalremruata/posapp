<?php

use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// Route::redirect('/', '/admin/login');
Route::get('{sale}/receipt/download',[InvoiceController::class, 'downloadInvoice'])
->name('sale.receipt.download');
Route::get('{product}/barcode',[BarcodeController::class, 'show'])
->name('barcode.show');
