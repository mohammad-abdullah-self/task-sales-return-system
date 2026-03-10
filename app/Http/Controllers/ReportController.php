<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function stock(Request $request)
    {
        $start = Carbon::parse($request->input('start_date', now()->subDays(30)->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();

        $items = Item::query()
            ->when($request->filled('item_id'), function ($query) use ($request) {
                $itemId = (int) $request->input('item_id');
                $query->whereKey($itemId);
            })
            ->orderBy('name')
            ->get();

        $inRange = DB::table('stock_transactions')
            ->selectRaw('item_id,
                SUM(CASE WHEN stock_effect > 0 THEN quantity ELSE 0 END) AS stock_in,
                SUM(CASE WHEN stock_effect < 0 THEN quantity ELSE 0 END) AS stock_out,
                SUM(quantity * stock_effect) AS net_in_range
            ')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        $afterStart = DB::table('stock_transactions')
            ->selectRaw('item_id, SUM(quantity * stock_effect) AS net_after_start')
            ->where('created_at', '>=', $start)
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        $report = $items->map(function (Item $item) use ($inRange, $afterStart) {
            $netAfterStart = (int) ($afterStart[$item->id]->net_after_start ?? 0);
            $opening = (int) $item->stock - $netAfterStart;

            $stockIn = (int) ($inRange[$item->id]->stock_in ?? 0);
            $stockOut = (int) ($inRange[$item->id]->stock_out ?? 0);
            $netInRange = (int) ($inRange[$item->id]->net_in_range ?? 0);
            $available = $opening + $netInRange;

            return [
                'item' => $item,
                'opening_stock' => $opening,
                'stock_in' => $stockIn,
                'stock_out' => $stockOut,
                'available_stock' => $available,
            ];
        });

        return view('reports.stock', [
            'items' => Item::query()->orderBy('name')->get(['id', 'name']),
            'start' => $start,
            'end' => $end,
            'report' => $report,
        ]);
    }
}
