@extends('layouts.app')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-4">
        <div>
            <div class="text-secondary small">Sales Return</div>
            <h1 class="h4 mb-1">{{ $salesReturn->return_number }}</h1>
            <div class="text-secondary">
                Invoice <a class="link-dark"
                    href="{{ route('invoices.show', $salesReturn->invoice) }}">#{{ $salesReturn->invoice_id }}</a>
                <span class="text-secondary">·</span> {{ $salesReturn->invoice->customer_name ?? '-' }}
            </div>
            <div class="text-secondary small">
                {{ $salesReturn->return_date->format('Y-m-d H:i') }} · Refund: {{ $salesReturn->refund_method }}
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('sales-returns.index') }}">Back to list</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Taxable</div>
                    <div class="fs-4 fw-semibold">{{ number_format((float) $salesReturn->taxable_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Discount</div>
                    <div class="fs-4 fw-semibold">{{ number_format((float) $salesReturn->discount_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">VAT</div>
                    <div class="fs-4 fw-semibold">{{ number_format((float) $salesReturn->vat_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Total</div>
                    <div class="fs-4 fw-semibold">{{ number_format((float) $salesReturn->total_amount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($salesReturn->return_reason)
        <div class="card mb-4">
            <div class="card-body">
                <div class="text-secondary small mb-1">Return reason</div>
                <div>{{ $salesReturn->return_reason }}</div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Tax rate</th>
                        <th>Discount</th>
                        <th>Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesReturn->salesReturnItems as $line)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $line->item->name }}</div>
                                <div class="small text-secondary">{{ $line->item->sku }}</div>
                            </td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ number_format((float) $line->unit_price, 2) }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $line->tax_rate, 4), '0'), '.') }}%</td>
                            <td>{{ number_format((float) $line->discount, 2) }}</td>
                            <td>{{ number_format((float) $line->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
