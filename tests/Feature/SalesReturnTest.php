<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\SalesReturn;
use App\Models\StockTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ✓ Return partial items
it('allows returning partial items from an invoice', function () {
    $item = Item::factory()->create(['stock' => 20, 'price' => 100]);

    $invoice = Invoice::factory()->create();

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'item_id' => $item->id,
        'quantity' => 5,
        'unit_price' => 100,
        'taxable_price' => 500,
        'discount' => 0,
        'tax_rate' => 15,
        'total' => 575,
    ]);

    $initialStock = $item->stock;

    $response = $this->post(route('sales-returns.store'), [
        'invoice_id' => $invoice->id,
        'return_date' => now()->toDateTimeString(),
        'refund_method' => 'Bank',
        'items' => [
            ['invoice_item_id' => $invoiceItem->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();

    $salesReturn = SalesReturn::where('invoice_id', $invoice->id)->first();
    expect($salesReturn)->not()->toBeNull();

    $this->assertDatabaseHas('sales_return_items', [
        'sales_return_id' => $salesReturn->id,
        'invoice_item_id' => $invoiceItem->id,
        'quantity' => 2,
    ]);

    $item->refresh();
    expect($item->stock)->toBe($initialStock + 2);
});

// ✓ Return all items
it('allows returning all items from an invoice', function () {
    $item = Item::factory()->create(['stock' => 20, 'price' => 100]);

    $invoice = Invoice::factory()->create();

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'item_id' => $item->id,
        'quantity' => 5,
        'unit_price' => 100,
        'taxable_price' => 500,
        'discount' => 0,
        'tax_rate' => 15,
        'total' => 575,
    ]);

    $initialStock = $item->stock;

    $response = $this->post(route('sales-returns.store'), [
        'invoice_id' => $invoice->id,
        'return_date' => now()->toDateTimeString(),
        'refund_method' => 'Cash',
        'items' => [
            ['invoice_item_id' => $invoiceItem->id, 'quantity' => 5],
        ],
    ]);

    $response->assertRedirect();

    $salesReturn = SalesReturn::where('invoice_id', $invoice->id)->first();
    expect($salesReturn)->not()->toBeNull();

    $this->assertDatabaseHas('sales_return_items', [
        'sales_return_id' => $salesReturn->id,
        'invoice_item_id' => $invoiceItem->id,
        'quantity' => 5,
    ]);

    $item->refresh();
    expect($item->stock)->toBe($initialStock + 5);
});

// ✓ Inventory update after return
it('updates inventory correctly after return', function () {
    $item = Item::factory()->create(['stock' => 10, 'price' => 50]);

    $invoice = Invoice::factory()->create();

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'item_id' => $item->id,
        'quantity' => 3,
        'unit_price' => 50,
        'taxable_price' => 150,
        'discount' => 0,
        'tax_rate' => 15,
        'total' => 172.5,
    ]);

    $initialStock = $item->stock;

    $response = $this->post(route('sales-returns.store'), [
        'invoice_id' => $invoice->id,
        'return_date' => now()->toDateTimeString(),
        'refund_method' => 'Bank',
        'items' => [
            ['invoice_item_id' => $invoiceItem->id, 'quantity' => 2],
        ],
    ]);

    $response->assertRedirect();

    $salesReturn = SalesReturn::where('invoice_id', $invoice->id)->first();

    $item->refresh();
    expect($item->stock)->toBe($initialStock + 2);

    $this->assertDatabaseHas('stock_transactions', [
        'item_id' => $item->id,
        'type' => StockTransaction::TYPE_RETURN,
        'reference_id' => $salesReturn->id,
        'quantity' => 2,
        'stock_effect' => 1,
    ]);
});

// ✓ Validation on over-return
it('prevents returning more than the sold quantity', function () {
    $item = Item::factory()->create(['stock' => 10, 'price' => 100]);

    $invoice = Invoice::factory()->create();

    $invoiceItem = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'item_id' => $item->id,
        'quantity' => 3,
        'unit_price' => 100,
        'taxable_price' => 300,
        'discount' => 0,
        'tax_rate' => 15,
        'total' => 345,
    ]);

    $response = $this->postJson(route('sales-returns.store'), [
        'invoice_id' => $invoice->id,
        'return_date' => now()->toDateTimeString(),
        'refund_method' => 'Credit Note',
        'items' => [
            ['invoice_item_id' => $invoiceItem->id, 'quantity' => 4],
        ],
    ]);

    $response->assertStatus(422);
});
