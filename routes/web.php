<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('invoices.index');
});

Route::get('/items', [ItemController::class, 'index'])->name('items.index');

Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
