@extends('layouts.app')

@section('title', 'Sales Returns Report |')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Sales Returns Report</h1>
        </div>
    </div>

    <form class="mb-3" method="get" action="{{ route('reports.sales-returns') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-auto">
                <label class="form-label small text-secondary mb-1">Customer</label>
                <input name="customer" value="{{ request('customer') }}" class="form-control form-control-sm" />
            </div>
            <div class="col-12 col-md-auto">
                <label class="form-label small text-secondary mb-1">Start date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="form-control form-control-sm" />
            </div>
            <div class="col-12 col-md-auto">
                <label class="form-label small text-secondary mb-1">End date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="form-control form-control-sm" />
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-dark btn-sm" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Return No</th>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Return date</th>
                        <th>Refund method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="fw-semibold">
                                <a class="link-dark"
                                    href="{{ route('sales-returns.show', $row) }}">{{ $row->return_number }}</a>
                            </td>
                            <td>
                                <a class="link-dark"
                                    href="{{ route('invoices.show', $row->invoice) }}">#{{ $row->invoice_id }}</a>
                            </td>
                            <td>{{ $row->invoice->customer_name ?? '-' }}</td>
                            <td>{{ $row->return_date->format('Y-m-d H:i') }}</td>
                            <td>{{ $row->refund_method }}</td>
                            <td>{{ number_format((float) $row->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="5">No returns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
