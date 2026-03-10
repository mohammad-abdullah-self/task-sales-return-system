<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\StockTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = Item::query()->orderBy('id')->get();

        DB::transaction(function () use ($items) {
            Invoice::factory()
                ->count(5)
                ->make()
                ->each(function (Invoice $invoice) use ($items) {
                    $invoice->save();

                    $picked = $items->random(mt_rand(2, 3))->values();

                    $taxableTotal = 0.0;
                    $discountTotal = 0.0;
                    $vatTotal = 0.0;
                    $grandTotal = 0.0;

                    foreach ($picked as $item) {
                        $qty = mt_rand(1, 5);

                        if ($item->stock < $qty) {
                            $item->stock = $qty + mt_rand(10, 50);
                        }

                        $unitPrice = (float) $item->price;
                        $taxRate = 15.0;

                        $taxable = $qty * $unitPrice;
                        $discount = round($taxable * (mt_rand(0, 10) / 100), 2);
                        $vat = round(($taxable - $discount) * ($taxRate / 100), 2);
                        $lineTotal = round(($taxable - $discount) + $vat, 2);

                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'item_id' => $item->id,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                            'taxable_price' => $taxable,
                            'discount' => $discount,
                            'tax_rate' => $taxRate,
                            'total' => $lineTotal,
                        ]);

                        $item->decrement('stock', $qty);

                        StockTransaction::create([
                            'item_id' => $item->id,
                            'type' => StockTransaction::TYPE_SALE,
                            'reference_id' => $invoice->id,
                            'quantity' => $qty,
                            'stock_effect' => -1,
                            'created_at' => $invoice->invoice_date,
                            'updated_at' => $invoice->invoice_date,
                        ]);

                        $taxableTotal += $taxable;
                        $discountTotal += $discount;
                        $vatTotal += $vat;
                        $grandTotal += $lineTotal;
                    }

                    $invoice->update([
                        'taxable_amount' => round($taxableTotal, 2),
                        'discount_amount' => round($discountTotal, 2),
                        'vat_amount' => round($vatTotal, 2),
                        'total_amount' => round($grandTotal, 2),
                    ]);
                });
        });
    }
}
