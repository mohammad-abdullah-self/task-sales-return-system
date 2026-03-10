@extends('layouts.app')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Invoices</h1>
            <div class="text-secondary">Sales invoices</div>
        </div>

        <form method="get" action="{{ route('invoices.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-auto">
                    <label class="form-label small text-secondary mb-1">Customer</label>
                    <input name="customer" value="{{ request('customer') }}" class="form-control form-control-sm" />
                </div>
                <div class="col-12 col-md-auto">
                    <label class="form-label small text-secondary mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Any</option>
                        <option value="paid" @selected(request('status') === 'paid')>paid</option>
                        <option value="unpaid" @selected(request('status') === 'unpaid')>unpaid</option>
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button class="btn btn-dark btn-sm" type="submit">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>
                                <a class="link-dark" href="{{ route('invoices.show', $invoice) }}">#{{ $invoice->id }}</a>
                            </td>
                            <td>{{ $invoice->customer_name }}</td>
                            <td>{{ $invoice->invoice_date->format('Y-m-d H:i') }}</td>
                            <td>{{ $invoice->invoice_items_count }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td>
                                <span
                                    class="badge {{ $invoice->status === 'paid' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $invoice->status }}
                                </span>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="7">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $invoices->links() }}
    </div>
@endsection
