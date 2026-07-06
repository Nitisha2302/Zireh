<?php

use App\Models\CustomerOrder;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.elim.base_url' => 'https://openapi.elim.asia']);
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);
});

function makeCustomerOrder(User $user, array $overrides = []): CustomerOrder
{
    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD0000000001',
        'status' => 'pending_payment',
        'payment_status' => 'unpaid',
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 10,
        'elim_service_fee_cny' => 5,
        'commission_amount' => 3,
        'customer_total_cny' => 118,
        'receiver_address' => [
            'name' => 'Warehouse',
            'phone' => '02812345678',
            'mobile' => '13800138000',
            'address' => '广州市天河区体育西路123号',
            'province' => '广东省',
            'city' => '广州市',
            'area' => '天河区',
        ],
    ], $overrides));
}

it('returns payment preview with wallet deficit', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user);
    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/auth/orders/{$order->id}/payment-preview");

    $response->assertOk()
        ->assertJsonPath('data.breakdown.total_cny', 118)
        ->assertJsonPath('data.wallet.deficit_cny', 118)
        ->assertJsonPath('data.can_pay', false);
});

it('pays order by deducting wallet and confirming with elim', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user);
    app(WalletService::class)->adminAddFunds(
        $user,
        200,
        'Test funds',
        \App\Models\Admin::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])
    );

    Http::fake([
        'https://openapi.elim.asia/v1/purchasing/orders/ORD0000000001/confirm' => Http::response([
            'order_id' => 'ORD0000000001',
            'goods_amount_cny' => 100,
            'shipping_fee_cny' => 10,
            'service_fee_cny' => 5,
            'total_amount_cny' => 115,
            'paid_at' => '2026-06-28T10:00:00.000Z',
            'balance_after' => 885,
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD0000000001' => Http::response([
            'data' => [
                'id' => 'ORD0000000001',
                'status' => 'paid',
                'payment_status' => 'paid',
            ],
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/auth/orders/{$order->id}/pay");

    $response->assertOk()
        ->assertJsonPath('data.payment_status', 'paid');

    expect((float) app(WalletService::class)->getBalance($user))->toBe(82.0)
        ->and($order->fresh()->payment_status)->toBe('paid')
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_ORDER_PAYMENT)->count())->toBe(1);
});

it('syncs order status from elim', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user, ['status' => 'pending_payment']);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/ORD0000000001' => Http::response([
            'data' => [
                'id' => 'ORD0000000001',
                'status' => 'shipped',
                'payment_status' => 'paid',
            ],
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/auth/orders/{$order->id}/sync");

    $response->assertOk()
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.payment_status', 'paid');

    expect($order->fresh()->status)->toBe('shipped');
});

it('cancels order via elim when status allows', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user, ['status' => 'pending_payment']);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/ORD0000000001/cancel' => Http::response([
            'success' => true,
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD0000000001' => Http::response([
            'data' => [
                'id' => 'ORD0000000001',
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ],
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/v1/auth/orders/{$order->id}/cancel");

    $response->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('rejects cancel when order is not cancellable', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user, ['status' => 'shipped']);
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/auth/orders/{$order->id}/cancel")
        ->assertUnprocessable();
});

it('fetches taobao logistics by package id', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/12345/logistic-detail' => Http::response([
            'data' => [
                ['status' => 'in_transit', 'time' => '2026-06-28T08:00:00.000Z'],
            ],
        ], 200),
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/auth/orders/{$order->id}/logistics?package_id=12345");

    $response->assertOk()
        ->assertJsonPath('data.platform', 'taobao')
        ->assertJsonPath('data.package_id', 12345);
});

it('returns 1688 logistics from synced order detail', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user, [
        'platform' => '1688',
        'elim_detail_snapshot' => [
            'data' => [
                'logistics' => [
                    'logistics_info' => [
                        ['status' => 'delivered'],
                    ],
                ],
            ],
        ],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/v1/auth/orders/{$order->id}/logistics");

    $response->assertOk()
        ->assertJsonPath('data.platform', '1688')
        ->assertJsonPath('data.source', 'order_detail');
});

it('refunds customer wallet when elim payment confirm fails', function () {
    $user = User::factory()->create();
    $order = makeCustomerOrder($user);
    app(WalletService::class)->adminAddFunds(
        $user,
        200,
        'Test funds',
        \App\Models\Admin::create([
            'name' => 'Admin2',
            'username' => 'admin2',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])
    );

    Http::fake([
        'https://openapi.elim.asia/v1/purchasing/orders/ORD0000000001/confirm' => Http::response([
            'error' => 'insufficient_balance',
            'deficit' => 50,
            'current_balance' => 65,
            'required' => 115,
        ], 400),
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/auth/orders/{$order->id}/pay")
        ->assertUnprocessable();

    expect((float) app(WalletService::class)->getBalance($user))->toBe(200.0)
        ->and($order->fresh()->payment_status)->toBe('unpaid')
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_ORDER_REFUND)->count())->toBe(1);
});
