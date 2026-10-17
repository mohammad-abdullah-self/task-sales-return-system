@extends('layouts.app')

@section('title', 'AI Stock Insights |')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h4 mb-1">AI Stock Insights</h1>
            <div class="text-secondary">Low-stock alerts and demand prediction from the last 30 days of sales.</div>
        </div>
        <a class="btn btn-outline-dark btn-sm" href="{{ route('reports.ai-stock', ['refresh' => 1]) }}">Refresh insights</a>
    </div>

    @if ($notice)
        <div class="alert alert-warning">{{ $notice }}</div>
    @endif

    <div class="text-secondary small mb-2">
        Source: {{ $source === 'gemini' ? 'Google Gemini' : 'Local heuristic' }}
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table-striped table-hover mb-0 table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>On-hand</th>
                        <th>30-day sold</th>
                        <th>30-day returned</th>
                        <th>Days of cover</th>
                        <th>Risk</th>
                        <th>Predicted 7d</th>
                        <th>Predicted 30d</th>
                        <th>Suggested reorder</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $risk = $row['risk'];
                            $badge = match ($risk) {
                                'high' => 'danger',
                                'medium' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['name'] }}</div>
                                <div class="small text-secondary">{{ $row['sku'] }}</div>
                            </td>
                            <td>{{ $row['stock'] }}</td>
                            <td>{{ $row['sold_qty'] }}</td>
                            <td>{{ $row['returned_qty'] }}</td>
                            <td>{{ (float) $row['avg_daily_sales'] <= 0 ? '—' : $row['days_of_cover'] }}</td>
                            <td>
                                <span class="badge text-bg-{{ $badge }}">{{ ucfirst($risk) }}</span>
                            </td>
                            <td>{{ $row['predicted_demand_7d'] }}</td>
                            <td>{{ $row['predicted_demand_30d'] }}</td>
                            <td>{{ $row['suggested_reorder_qty'] }}</td>
                            <td class="small">{{ $row['reason'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-secondary py-5 text-center" colspan="10">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
