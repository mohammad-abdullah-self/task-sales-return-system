<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use function Symfony\Component\Clock\now;

class SalesReturnController extends Controller
{
    private const REFUND_METHODS = ['Cash', 'Bank', 'Credit Note'];

    public function index(Request $request)
    {
        $salesReturns = SalesReturn::query()->with(['invoice'])
            ->when($request->filled('customer'), function ($query) use ($request) {
                $customer = $request->string('customer')->toString();
                $query->whereHas('invoice', fn ($q) => $q->where('customer_name', 'like', "%{$customer}%"));
            })
            ->when($request->filled('invoice_id'), function ($query) use ($request) {
                $query->where('invoice_id', (int) $request->input('invoice_id'));
            })
            ->when($request->filled('start_date'), function ($query) use ($request) {
                $query->where('return_date', '>=', $request->date('start_date')->startOfDay());
            })
            ->when($request->filled('end_date'), function ($query) use ($request) {
                $query->where('return_date', '<=', $request->date('end_date')->endOfDay());
            })->orderByDesc('return_date')
            ->paginate(20)
            ->withQueryString();

        return view('sales_returns.index', [
            'salesReturns' => $salesReturns,
            'refundMethods' => self::REFUND_METHODS,
        ]);
    }

    public function create(Request $request)
    {
        $invoices = Invoice::query()
            ->whereDoesntHave('salesReturn')
            ->orderByDesc('invoice_date')
            ->get();

        $selectedInvoice = null;
        if ($request->filled('invoice_id')) {
            $selectedInvoice = Invoice::query()
                ->whereKey((int) $request->input('invoice_id'))
                ->whereDoesntHave('salesReturn')
                ->with(['invoiceItems.item'])
                ->first();
        }

        return view('sales_returns.create', [
            'invoices' => $invoices,
            'invoice' => $selectedInvoice,
            'refundMethods' => self::REFUND_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id', 'unique:sales_returns,invoice_id'],
            'refund_method' => ['required', Rule::in(self::REFUND_METHODS)],
            'return_reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer', 'exists:invoice_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $invoice = Invoice::query()
            ->with(['invoiceItems.item'])
            ->findOrFail($validated['invoice_id']);

        $items = $validated['items'];

        return DB::transaction(function () use ($invoice, $validated, $items) {
            $invoiceItemsById = $invoice->invoiceItems->keyBy('id');

            $lines = [];
            foreach ($items as $row) {
                $qty = (int) $row['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $invoiceItemId = (int) $row['invoice_item_id'];

                $invoiceItem = $invoiceItemsById->get($invoiceItemId);
                if (! $invoiceItem) {
                    abort(422, 'Invalid invoice item for this invoice.');
                }

                if ($qty > (int) $invoiceItem->quantity) {
                    abort(422, 'Return quantity must not exceed sold quantity.');
                }

                $lines[] = [$invoiceItem, $qty];
            }

            if (count($lines) === 0) {
                abort(422, 'Select at least one item with return quantity.');
            }

            $taxableTotal = 0.0;
            $discountTotal = 0.0;
            $vatTotal = 0.0;
            $grandTotal = 0.0;

            foreach ($lines as [$invoiceItem, $qty]) {
                $unitPrice = (float) $invoiceItem->unit_price;
                $taxRate = (float) $invoiceItem->tax_rate;

                $taxable = round($unitPrice * $qty, 2);
                $perUnitDiscount = ((int) $invoiceItem->quantity) > 0
                    ? ((float) $invoiceItem->discount / (int) $invoiceItem->quantity)
                    : 0.0;
                $discount = round($perUnitDiscount * $qty, 2);
                $vat = round(($taxable - $discount) * ($taxRate / 100), 2);
                $total = round(($taxable - $discount) + $vat, 2);

                $taxableTotal += $taxable;
                $discountTotal += $discount;
                $vatTotal += $vat;
                $grandTotal += $total;
            }

            $salesReturn = SalesReturn::create([
                'return_number' => 'SR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'invoice_id' => $invoice->id,
                'return_date' => now(),
                'refund_method' => $validated['refund_method'],
                'return_reason' => $validated['return_reason'] ?? null,
                'taxable_amount' => round($taxableTotal, 2),
                'discount_amount' => round($discountTotal, 2),
                'vat_amount' => round($vatTotal, 2),
                'total_amount' => round($grandTotal, 2),
            ]);

            foreach ($lines as [$invoiceItem, $qty]) {
                $unitPrice = (float) $invoiceItem->unit_price;
                $taxRate = (float) $invoiceItem->tax_rate;

                $taxable = round($unitPrice * $qty, 2);
                $perUnitDiscount = ((int) $invoiceItem->quantity) > 0
                    ? ((float) $invoiceItem->discount / (int) $invoiceItem->quantity)
                    : 0.0;
                $discount = round($perUnitDiscount * $qty, 2);
                $vat = round(($taxable - $discount) * ($taxRate / 100), 2);
                $total = round(($taxable - $discount) + $vat, 2);

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'item_id' => $invoiceItem->item_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'taxable_price' => $taxable,
                    'discount' => $discount,
                    'tax_rate' => $taxRate,
                    'total' => $total,
                ]);

                $invoiceItem->item->increment('stock', $qty);

                StockTransaction::create([
                    'item_id' => $invoiceItem->item_id,
                    'type' => StockTransaction::TYPE_RETURN,
                    'reference_id' => $salesReturn->id,
                    'quantity' => $qty,
                    'stock_effect' => 1,
                    'created_at' => $salesReturn->return_date,
                    'updated_at' => $salesReturn->return_date,
                ]);
            }

            return redirect()
                ->route('sales-returns.show', $salesReturn)
                ->with('status', 'Sales return created successfully.');
        });
    }

    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load(['invoice', 'salesReturnItems.item']);

        return view('sales_returns.show', [
            'salesReturn' => $salesReturn,
        ]);
    }
}
