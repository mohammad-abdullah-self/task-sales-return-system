<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->withCount('invoiceItems')
            ->when($request->filled('customer'), function ($query) use ($request) {
                $customer = $request->string('customer')->toString();
                $query->where('customer_name', 'like', "%{$customer}%");
            })->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->string('status')->toString();
                $query->where('status', $status);
            })->orderByDesc('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['invoiceItems.item']);

        return view('invoices.show', [
            'invoice' => $invoice,
        ]);
    }
}
