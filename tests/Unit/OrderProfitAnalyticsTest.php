<?php

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use App\Models\User;
use App\Services\Currency\CurrencyExchangeService;
use App\Services\Order\OrderProfitAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calculates item and order profit from cny cost and tjs sell price', function () {
    config(['services.exchange_rate.default_rate' => 1.5]);

    $user = User::factory()->create();

    $order = CustomerOrder::query()->create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'status' => 'paid',
        'payment_status' => 'paid',
        'payment_method' => 'online',
        'goods_subtotal_cny' => 10,
        'shipping_fee_cny' => 10,
        'elim_service_fee_cny' => 5,
        'commission_amount' => 0,
        'customer_total_cny' => 0,
        'exchange_rate' => 1.5,
        'customer_total_tjs' => 120.50,
        'final_amount_tjs' => 120.50,
    ]);

    $item = CustomerOrderItem::query()->create([
        'customer_order_id' => $order->id,
        'product_id' => 'p1',
        'quantity' => 1,
        'unit_price' => 10,
        'line_subtotal' => 10,
        'final_amount_tjs' => 120.50,
        'product_snapshot' => ['title' => 'Test'],
    ]);

    $analytics = app(OrderProfitAnalytics::class)->forOrder($order->load('items'));
    $line = $analytics['items'][$item->id];

    expect($analytics['rate'])->toBe(1.5)
        ->and($line['cost_tjs'])->toBe(15.0)
        ->and($line['sell_tjs'])->toBe(120.5)
        ->and($line['profit_tjs'])->toBe(105.5)
        ->and($line['margin_percent'])->toBe(87.55)
        ->and($analytics['goods_cost_tjs'])->toBe(15.0)
        ->and($analytics['fee_cost_tjs'])->toBe(22.5)
        ->and($analytics['revenue_tjs'])->toBe(120.5)
        ->and($analytics['profit_tjs'])->toBe(83.0)
        ->and($analytics['margin_percent'])->toBe(68.88);
});

it('falls back to the live exchange rate when the order has none stored', function () {
    $user = User::factory()->create();

    $order = CustomerOrder::query()->create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'status' => 'paid',
        'payment_status' => 'paid',
        'payment_method' => 'online',
        'goods_subtotal_cny' => 10,
        'shipping_fee_cny' => 0,
        'exchange_rate' => null,
        'final_amount_tjs' => 20,
    ]);

    CustomerOrderItem::query()->create([
        'customer_order_id' => $order->id,
        'product_id' => 'p1',
        'quantity' => 1,
        'unit_price' => 10,
        'line_subtotal' => 10,
        'final_amount_tjs' => 20,
        'product_snapshot' => ['title' => 'Test'],
    ]);

    $liveRate = app(CurrencyExchangeService::class)->getRate();
    $analytics = app(OrderProfitAnalytics::class)->forOrder($order->load('items'));

    expect($analytics['rate'])->toBe($liveRate)
        ->and($analytics['goods_cost_tjs'])->toBe(round(10 * $liveRate, 2));
});

it('detects the matching commission slab and percentage from goods cny', function () {
    $user = User::factory()->create();
    $platform = Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao'],
        'logo' => [],
        'is_available' => true,
    ]);

    $slab = PlatformCommissionSlab::create([
        'platform_id' => $platform->id,
        'min_amount' => 0,
        'max_amount' => 100,
        'commission_percentage' => 5,
        'is_active' => true,
    ]);

    $order = CustomerOrder::query()->create([
        'user_id' => $user->id,
        'platform_id' => $platform->id,
        'platform' => 'taobao',
        'status' => 'paid',
        'payment_status' => 'paid',
        'payment_method' => 'online',
        'goods_subtotal_cny' => 20,
        'shipping_fee_cny' => 0,
        'exchange_rate' => 1.5,
        'final_amount_tjs' => 50,
        'commission_slab_id' => null,
        'commission_percentage' => 0,
    ]);

    CustomerOrderItem::query()->create([
        'customer_order_id' => $order->id,
        'product_id' => 'p1',
        'quantity' => 1,
        'unit_price' => 20,
        'line_subtotal' => 20,
        'final_amount_tjs' => 50,
        'product_snapshot' => ['title' => 'Test'],
    ]);

    $analytics = app(OrderProfitAnalytics::class)->forOrder($order->load('items'));

    expect($analytics['commission_slab_id'])->toBe($slab->id)
        ->and($analytics['commission_percentage'])->toBe(5.0)
        ->and($analytics['commission_range_label'])->toBe('0.00–100.00 CNY');
});
