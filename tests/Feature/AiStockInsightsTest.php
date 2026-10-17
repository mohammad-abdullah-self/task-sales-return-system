<?php

use App\Models\Item;
use App\Models\StockTransaction;
use App\Services\Inventory\DemandForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('renders the AI stock insights page from the reports menu', function () {
    config(['services.gemini.key' => '']);

    $item = Item::factory()->create(['name' => 'Widget A', 'stock' => 40]);

    StockTransaction::create([
        'item_id' => $item->id,
        'type' => StockTransaction::TYPE_SALE,
        'reference_id' => 1,
        'quantity' => 10,
        'stock_effect' => -1,
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('reports.ai-stock'));

    $response->assertOk();
    $response->assertSee('AI Stock Insights');
    $response->assertSee('Widget A');
    $response->assertSee('Gemini API key not set; showing heuristic estimates.');
});

it('uses Gemini JSON when an API key is configured', function () {
    config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-2.0-flash']);
    Http::preventStrayRequests();

    $item = Item::factory()->create(['name' => 'Gadget B', 'stock' => 5]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'items' => [
                                        [
                                            'item_id' => $item->id,
                                            'risk' => 'high',
                                            'predicted_demand_7d' => 12,
                                            'predicted_demand_30d' => 48,
                                            'suggested_reorder_qty' => 60,
                                            'reason' => 'Mocked Gemini low-stock alert.',
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->get(route('reports.ai-stock'));

    $response->assertOk();
    $response->assertSee('Gadget B');
    $response->assertSee('Mocked Gemini low-stock alert.');
    $response->assertSee('12');
    $response->assertSee('High');
    $response->assertDontSee('Gemini API key not set; showing heuristic estimates.');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'generativelanguage.googleapis.com')
            && str_contains($request->url(), 'gemini-2.0-flash:generateContent');
    });
});

it('skips HTTP when the Gemini API key is empty', function () {
    config(['services.gemini.key' => '']);
    Http::preventStrayRequests();
    Http::fake();

    Item::factory()->create();

    $this->get(route('reports.ai-stock'))->assertOk();

    Http::assertNothingSent();
});

it('re-calls Gemini when refresh=1 after a cached response', function () {
    config(['services.gemini.key' => 'test-key']);
    Http::preventStrayRequests();

    $item = Item::factory()->create();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'items' => [
                                        [
                                            'item_id' => $item->id,
                                            'risk' => 'low',
                                            'predicted_demand_7d' => 1,
                                            'predicted_demand_30d' => 4,
                                            'suggested_reorder_qty' => 0,
                                            'reason' => 'Cached then refreshed.',
                                        ],
                                    ],
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $this->get(route('reports.ai-stock'))->assertOk();
    $this->get(route('reports.ai-stock'))->assertOk();
    Http::assertSentCount(1);

    $this->get(route('reports.ai-stock', ['refresh' => 1]))->assertOk();
    Http::assertSentCount(2);

    expect(Cache::has(DemandForecastService::CACHE_KEY))->toBeTrue();
});
