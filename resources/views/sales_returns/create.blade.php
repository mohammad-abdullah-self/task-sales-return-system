@extends('layouts.app')

@section('title', 'Create Sales Return |')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Create Sales Return</h1>
            <div class="text-secondary">Select invoice, choose return quantities, and save.</div>
        </div>
    </div>

    <form method="get" action="{{ route('sales-returns.create') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-8">
                    <label class="form-label fw-semibold mb-1">Select Invoice</label>
                    <select name="invoice_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose invoice --</option>
                        @foreach ($invoices as $inv)
                            <option value="{{ $inv->id }}" @selected((int) request('invoice_id') === $inv->id)>
                                #{{ $inv->id }} · {{ $inv->customer_name }} ·
                                {{ $inv->invoice_date->format('Y-m-d') }} ·
                                {{ number_format((float) $inv->total_amount, 2) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Only invoices without an existing return are shown.</div>
                </div>
            </div>
        </div>
    </form>

    @if (!$invoice)
        <div class="alert alert-info">
            Select an invoice to load its line items.
        </div>
    @else
        <form method="post" action="{{ route('sales-returns.store') }}" class="d-grid gap-3" id="salesReturnForm">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div>
                            <label class="form-label fw-semibold mb-1">Refund method</label>
                            <select name="refund_method" class="form-select">
                                @foreach ($refundMethods as $method)
                                    <option value="{{ $method }}" @selected(old('refund_method') === $method)>{{ $method }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold mb-1">Return reason</label>
                            <textarea name="return_reason" rows="2" class="form-control" placeholder="Optional">{{ old('return_reason') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Sold qty</th>
                                <th>Unit</th>
                                <th>Tax rate</th>
                                <th>Return qty</th>
                                <th>Return total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->invoiceItems as $i => $line)
                                @php
                                    $soldQty = (int) $line->quantity;
                                    $unitPrice = (float) $line->unit_price;
                                    $taxRate = (float) $line->tax_rate;
                                    $perUnitDiscount = $soldQty > 0 ? (float) $line->discount / $soldQty : 0.0;
                                @endphp
                                <tr data-row>
                                    <td>
                                        <div>{{ $line->item->name }}</div>
                                        <div class="small text-secondary">{{ $line->item->sku }}</div>
                                        <input type="hidden" name="items[{{ $i }}][invoice_item_id]"
                                            value="{{ $line->id }}">
                                    </td>
                                    <td>{{ $soldQty }}</td>
                                    <td>{{ number_format($unitPrice, 2) }}</td>
                                    <td>{{ rtrim(rtrim(number_format($taxRate, 4), '0'), '.') }}%</td>
                                    <td>
                                        <input type="number" min="0" max="{{ $soldQty }}" step="1"
                                            name="items[{{ $i }}][quantity]"
                                            value="{{ old("items.$i.quantity", 0) }}"
                                            class="form-control form-control-sm text-end" data-qty
                                            data-unit-price="{{ $unitPrice }}"
                                            data-per-unit-discount="{{ $perUnitDiscount }}"
                                            data-tax-rate="{{ $taxRate }}" />
                                    </td>
                                    <td data-line-total>0.00</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Totals</div>
                            <div class="row g-2 small">
                                <div class="col-6 text-secondary">Subtotal</div>
                                <div class="col-6 text-end fw-semibold " data-subtotal>0.00</div>
                                <div class="col-6 text-secondary">Discount</div>
                                <div class="col-6 text-end fw-semibold " data-discount>0.00</div>
                                <div class="col-6 text-secondary">VAT</div>
                                <div class="col-6 text-end fw-semibold " data-vat>0.00</div>
                                <div class="col-6 fw-semibold">Net return</div>
                                <div class="col-6 text-end fw-bold " data-net>0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body d-flex justify-content-end">
                            <button type="submit" class="btn btn-dark btn-lg">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <script>
            (function() {
                const format = (n) => (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);
                const qtyInputs = document.querySelectorAll('[data-qty]');

                function reCalc() {
                    let subtotal = 0,
                        discount = 0,
                        vat = 0,
                        net = 0;

                    qtyInputs.forEach((input) => {
                        const qty = Math.max(0, parseInt(input.value || '0', 10));
                        const max = parseInt(input.getAttribute('max') || '0', 10);
                        if (qty > max) input.value = max;

                        const unitPrice = parseFloat(input.dataset.unitPrice || '0');
                        const perUnitDiscount = parseFloat(input.dataset.perUnitDiscount || '0');
                        const taxRate = parseFloat(input.dataset.taxRate || '0');

                        const lineSubtotal = unitPrice * qty;
                        const lineDiscount = perUnitDiscount * qty;
                        const lineVat = (lineSubtotal - lineDiscount) * (taxRate / 100);
                        const lineTotal = (lineSubtotal - lineDiscount) + lineVat;

                        subtotal += lineSubtotal;
                        discount += lineDiscount;
                        vat += lineVat;
                        net += lineTotal;

                        const row = input.closest('[data-row]');
                        if (row) {
                            const cell = row.querySelector('[data-line-total]');
                            if (cell) cell.textContent = format(lineTotal);
                        }
                    });

                    document.querySelector('[data-subtotal]').textContent = format(subtotal);
                    document.querySelector('[data-discount]').textContent = format(discount);
                    document.querySelector('[data-vat]').textContent = format(vat);
                    document.querySelector('[data-net]').textContent = format(net);
                }

                qtyInputs.forEach((el) => el.addEventListener('input', reCalc));
                reCalc();
            })();
        </script>
    @endif
@endsection
