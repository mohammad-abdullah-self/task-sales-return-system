@extends('layouts.app')

@section('title', 'Items |')

@section('content')
    <div class="d-flex align-items-end justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h4 mb-1">Items</h1>
            <div class="text-secondary">Current stock and default sale prices.</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table-striped table-hover mb-0 table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->sku }}</td>
                            <td>{{ $item->stock }}</td>
                            <td>{{ number_format((float) $item->price, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary py-5 text-center" colspan="4">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $items->links() }}</div>
@endsection
