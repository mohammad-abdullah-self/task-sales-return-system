<?php

namespace App\Services\Inventory;

use App\Models\Item;
use App\Models\StockTransaction;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class DemandForecastService
{
    public const CACHE_KEY = 'ai-stock-insights';

    public const CACHE_TTL_SECONDS = 900;

    public const LOOKBACK_DAYS = 30;

    public function __construct(private GeminiClient $gemini) {}

    /**
     * @return array{rows: list<array<string, mixed>>, source: string, notice: string|null}
     */
    public function insights(bool $refresh = false): array
    {
        $stats = $this->itemStats();

        if (! $this->gemini->isConfigured()) {
            return [
                'rows' => $this->applyHeuristic($stats),
                'source' => 'heuristic',
                'notice' => 'Gemini API key not set; showing heuristic estimates.',
            ];
        }

        if ($refresh) {
            Cache::forget(self::CACHE_KEY);
        }

        try {
            $predictions = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () use ($stats) {
                return $this->askGemini($stats);
            });

            return [
                'rows' => $this->mergePredictions($stats, $predictions),
                'source' => 'gemini',
                'notice' => null,
            ];
        } catch (Throwable $exception) {
            return [
                'rows' => $this->applyHeuristic($stats),
                'source' => 'heuristic',
                'notice' => 'Gemini request failed; showing heuristic estimates. '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function itemStats(): Collection
    {
        $items = Item::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock', 'price']);

        $start = now()->subDays(self::LOOKBACK_DAYS)->startOfDay();

        $aggregates = DB::table('stock_transactions')
            ->selectRaw('item_id,
                SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) AS sold_qty,
                SUM(CASE WHEN type = ? THEN quantity ELSE 0 END) AS returned_qty
            ', [StockTransaction::TYPE_SALE, StockTransaction::TYPE_RETURN])
            ->where('created_at', '>=', $start)
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        return $items->map(function (Item $item) use ($aggregates) {
            $sold = (int) ($aggregates[$item->id]->sold_qty ?? 0);
            $returned = (int) ($aggregates[$item->id]->returned_qty ?? 0);
            $netSales = max(0, $sold - $returned);
            $avgDailySales = round($netSales / self::LOOKBACK_DAYS, 2);
            $stock = (int) $item->stock;
            $daysOfCover = $avgDailySales > 0
                ? round($stock / $avgDailySales, 1)
                : 999.0;

            return [
                'item_id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'stock' => $stock,
                'price' => $item->price,
                'sold_qty' => $sold,
                'returned_qty' => $returned,
                'avg_daily_sales' => $avgDailySales,
                'days_of_cover' => $daysOfCover,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stats
     * @return list<array<string, mixed>>
     */
    public function applyHeuristic(Collection $stats): array
    {
        return $stats->map(function (array $row) {
            $prediction = $this->heuristicFor($row);

            return array_merge($row, $prediction);
        })->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stats
     * @return array<int, array<string, mixed>>
     */
    private function askGemini(Collection $stats): array
    {
        $payload = $stats->map(fn (array $row) => [
            'item_id' => $row['item_id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'on_hand' => $row['stock'],
            'sold_30d' => $row['sold_qty'],
            'returned_30d' => $row['returned_qty'],
            'avg_daily_sales' => $row['avg_daily_sales'],
            'days_of_cover' => $row['days_of_cover'],
        ])->all();

        $prompt = <<<'PROMPT'
You are an inventory analyst for a sales-return demo system.
Given item stock and the last 30 days of sales/returns, return JSON only in this shape:
{"items":[{"item_id":1,"risk":"high","predicted_demand_7d":10,"predicted_demand_30d":40,"suggested_reorder_qty":50,"reason":"short explanation"}]}

Rules:
- risk must be one of high, medium, low
- high when days_of_cover is under 7 or on_hand is 0 with any sales
- medium when days_of_cover is under 14
- otherwise low
- predicted_demand_* and suggested_reorder_qty must be non-negative integers
- reason must be one short sentence
- include every item_id from the input
PROMPT;

        $prompt .= "\n\nInput:\n".json_encode(['as_of' => Carbon::now()->toDateString(), 'items' => $payload], JSON_THROW_ON_ERROR);

        $decoded = $this->gemini->generateJson($prompt);
        $items = $decoded['items'] ?? $decoded;

        if (! is_array($items)) {
            return [];
        }

        $indexed = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['item_id'])) {
                continue;
            }

            $indexed[(int) $item['item_id']] = $item;
        }

        return $indexed;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $stats
     * @param  array<int, array<string, mixed>>  $predictions
     * @return list<array<string, mixed>>
     */
    private function mergePredictions(Collection $stats, array $predictions): array
    {
        return $stats->map(function (array $row) use ($predictions) {
            $prediction = $predictions[$row['item_id']] ?? null;
            $fallback = $this->heuristicFor($row);

            if ($prediction === null) {
                return array_merge($row, $fallback);
            }

            $risk = strtolower((string) ($prediction['risk'] ?? $fallback['risk']));
            if (! in_array($risk, ['high', 'medium', 'low'], true)) {
                $risk = $fallback['risk'];
            }

            return array_merge($row, [
                'risk' => $risk,
                'predicted_demand_7d' => max(0, (int) ($prediction['predicted_demand_7d'] ?? $fallback['predicted_demand_7d'])),
                'predicted_demand_30d' => max(0, (int) ($prediction['predicted_demand_30d'] ?? $fallback['predicted_demand_30d'])),
                'suggested_reorder_qty' => max(0, (int) ($prediction['suggested_reorder_qty'] ?? $fallback['suggested_reorder_qty'])),
                'reason' => (string) ($prediction['reason'] ?? $fallback['reason']),
            ]);
        })->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{risk: string, predicted_demand_7d: int, predicted_demand_30d: int, suggested_reorder_qty: int, reason: string}
     */
    private function heuristicFor(array $row): array
    {
        $avgDaily = (float) $row['avg_daily_sales'];
        $daysOfCover = (float) $row['days_of_cover'];
        $stock = (int) $row['stock'];
        $sold = (int) $row['sold_qty'];

        $risk = 'low';
        if (($stock === 0 && $sold > 0) || $daysOfCover < 7) {
            $risk = 'high';
        } elseif ($daysOfCover < 14) {
            $risk = 'medium';
        }

        $predicted7 = (int) max(0, round($avgDaily * 7));
        $predicted30 = (int) max(0, round($avgDaily * 30));
        $suggested = max(0, $predicted30 - $stock);

        $reason = match ($risk) {
            'high' => 'On-hand stock covers fewer than 7 days of recent sales.',
            'medium' => 'On-hand stock covers fewer than 14 days of recent sales.',
            default => 'Stock cover looks adequate versus the last 30 days of sales.',
        };

        return [
            'risk' => $risk,
            'predicted_demand_7d' => $predicted7,
            'predicted_demand_30d' => $predicted30,
            'suggested_reorder_qty' => $suggested,
            'reason' => $reason,
        ];
    }
}
