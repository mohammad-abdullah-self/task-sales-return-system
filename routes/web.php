<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('invoices.index');
});

Route::get('/items', [ItemController::class, 'index'])->name('items.index');

Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');

Route::get('/sales-returns', [SalesReturnController::class, 'index'])->name('sales-returns.index');
Route::get('/sales-returns/create', [SalesReturnController::class, 'create'])->name('sales-returns.create');
Route::post('/sales-returns', [SalesReturnController::class, 'store'])->name('sales-returns.store');
Route::get('/sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])->name('sales-returns.show');

Route::get('/reports/sales-returns', [ReportController::class, 'salesReturns'])->name('reports.sales-returns');
