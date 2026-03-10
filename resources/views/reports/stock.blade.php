@extends('layouts.app')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1">Stock Report</h1>
            <div class="text-secondary">Opening Stock, Stock IN, Stock Out, Available Stock.</div>
        </div>
    </div>

    <form class="mb-3" method="get" action="{{ route('reports.stock') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5 col-lg-4">
                <label class="form-label small text-secondary mb-1">Item</label>
                <select name="item_id" class="form-select form-select-sm">
                    <option value="">All items</option>
                    @foreach ($items as $it)
                        <option value="{{ $it->id }}" @selected((string) request('item_id') === (string) $it->id)>{{ $it->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto">
                <label class="form-label small text-secondary mb-1">Start date</label>
                <input type="date" name="start_date" value="{{ request('start_date', $start->toDateString()) }}"
                    class="form-control form-control-sm" />
            </div>
            <div class="col-12 col-md-auto">
                <label class="form-label small text-secondary mb-1">End date</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end->toDateString()) }}"
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
                        <th>Item</th>
                        <th>Opening Stock</th>
                        <th>Stock IN</th>
                        <th>Stock Out</th>
                        <th>Available Stock</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['item']->name }}</div>
                                <div class="small text-secondary">{{ $row['item']->sku }}</div>
                            </td>
                            <td>{{ $row['opening_stock'] }}</td>
                            <td>{{ $row['stock_in'] }}</td>
                            <td>{{ abs($row['stock_out']) }}</td>
                            <td>{{ $row['available_stock'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-5" colspan="5">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-2 text-secondary small">
        Range: {{ $start->toDateString() }} → {{ $end->toDateString() }}
    </div>
@endsection
