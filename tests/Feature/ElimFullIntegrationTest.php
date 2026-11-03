<?php

use App\Livewire\Admin\Order\OrderDetailPage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\ElimApiLog;
use App\Models\OrderStatus;
use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserCartItem;
use App\Models\UserWallet;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Services\Elim\ElimAuthService;
use App\Services\Elim\ElimWebhookService;
use App\Services\Wallet\WalletService;
use App\Support\Elim\ElimApiConfig;
use App\Support\Elim\ElimWarehouseAddress;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);

    config([
        'services.elim.base_url' => 'https://openapi.elim.asia',
        'services.elim.email' => 'agent@example.com',
        'services.elim.password' => 'secret',
        'services.elim.api_key' => null,
        'services.elim.webhook_secret' => null,
        'services.elim.payment_mode' => 'confirm',
        'services.elim.demo_mode' => false,
    ]);

    app(ElimApiConfig::class)->clearCache();
    Cache::put('elim:auth:access_token', 'test-token', 3600);

    $taobao = Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao'],
        'logo' => [],
        'is_available' => true,
    ]);
    $platform1688 = Platform::create([
        'code' => '1688',
        'name' => ['en' => '1688'],
        'logo' => [],
        'is_available' => true,
    ]);

    foreach ([$taobao, $platform1688] as $platform) {
        PlatformCommissionSlab::create([
            'platform_id' => $platform->id,
            'min_amount' => 0,
            'max_amount' => 100000,
            'commission_percentage' => 5,
            'is_active' => true,
        ]);
    }
});

function createElimOrderForUser(User $user, array $overrides = []): CustomerOrder
{
    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD0000000001',
        'status' => OrderStatus::CODE_PAID,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_WALLET,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_UNPAID,
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 10,
        'elim_service_fee_cny' => 5,
        'commission_amount' => 3,
        'customer_total_cny' => 118,
        'customer_total_tjs' => 118,
        'final_amount_tjs' => 118,
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

it('uses static x-api-key header when configured and verifies plan-status', function () {
    Setting::setValue(ElimApiConfig::SETTING_API_KEY, 'elim_live_static_key_123');
    app(ElimApiConfig::class)->refreshAfterSettingsUpdate();

    Http::fake([
        'https://openapi.elim.asia/v1/me/plan-status' => Http::response([
            'success' => true,
            'data' => [
                'plan_name' => 'Enterprise',
                'status' => 'active',
                'is_active' => true,
            ],
        ], 200),
    ]);

    $authService = app(ElimAuthService::class);
    $status = $authService->planStatus();

    expect(data_get($status, 'data.plan_name'))->toBe('Enterprise');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openapi.elim.asia/v1/me/plan-status'
            && $request->hasHeader('x-api-key', 'elim_live_static_key_123');
    });
});

it('calls Product API v2 search with ru language, keywordTranslate, and normalized camelCase filter keys', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/search' => Http::response([
            'success' => true,
            'data' => [
                'items' => [
                    [
                        'id' => '6889001',
                        'mp_id' => '6889001',
                        'mi_id' => '1688_6889001',
                        'title' => 'Оптовая куртка',
                        'price' => 45.5,
                        'coupon_price' => 42.0,
                        'quote_type' => 'by_volume',
                        'images' => ['https://img.example.com/1.jpg'],
                    ],
                ],
                'total' => 1,
            ],
        ], 200),
    ]);

    $this->getJson('/api/v1/1688/search?keyword=куртка&lang=ru&keyword_translate=true&filter[price_range][min]=10&filter[is_factory]=true')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.items.0.id', '6889001')
        ->assertJsonPath('data.items.0.mi_id', '1688_6889001')
        ->assertJsonPath('data.items.0.coupon_price', 42)
        ->assertJsonPath('data.items.0.quote_type', 'by_volume');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openapi.elim.asia/v2/products/search'
            && $request['platform'] === 'alibaba'
            && $request['lang'] === 'ru'
            && $request['keywordTranslate'] === true
            && data_get($request->data(), 'filter.priceRange.min') == 10
            && data_get($request->data(), 'filter.isFactory') === true;
    });
});

it('fetches Product API v2 find and detail with tiered volume pricing and spec_id', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/find' => Http::response([
            'success' => true,
            'data' => [
                'id' => '777888',
                'mp_id' => '777888',
                'mi_id' => '1688_777888',
                'title' => 'Industrial Sensor',
                'price' => 100,
                'quote_type' => 'by_volume',
                'price_range' => [
                    ['begin_amount' => 1, 'price' => 100],
                    ['begin_amount' => 10, 'price' => 85],
                ],
                'property_images' => ['prop_1' => 'https://img.example.com/p1.jpg'],
                'seller_mix_setting' => ['mix_amount' => 500],
                'skus' => [
                    [
                        'id' => 'sku_1',
                        'sku_id' => 'sku_1',
                        'spec_id' => 'spec_abc_123',
                        'price' => 100,
                        'stock' => 500,
                    ],
                ],
            ],
        ], 200),
        'https://openapi.elim.asia/v2/products/detail' => Http::response([
            'success' => true,
            'data' => [
                'id' => '777888',
                'description' => '<p>Detailed HTML specs</p>',
                'detail_images' => ['https://img.example.com/d1.jpg'],
                'attributes' => [['name' => 'Material', 'value' => 'Steel']],
            ],
        ], 200),
    ]);

    $this->getJson('/api/v1/1688/products/777888?lang=en')
        ->assertOk()
        ->assertJsonPath('data.mi_id', '1688_777888')
        ->assertJsonPath('data.quote_type', 'by_volume')
        ->assertJsonPath('data.skus.0.spec_id', 'spec_abc_123');

    $this->getJson('/api/v1/1688/products/777888/detail?lang=en')
        ->assertOk()
        ->assertJsonPath('data.description', '<p>Detailed HTML specs</p>');
});

it('supports imageHandle in upload-image and search-by-image', function () {
    Http::fake([
        'https://openapi.elim.asia/v1/products/upload-image' => Http::response([
            'success' => true,
            'data' => [
                'imageHandle' => 'handle_xyz_999',
            ],
        ], 200),
        'https://openapi.elim.asia/v1/products/search-by-image' => Http::response([
            'success' => true,
            'data' => [
                'items' => [
                    ['id' => 'tb_1', 'title' => 'Matched Shoe', 'price' => 99],
                ],
            ],
        ], 200),
    ]);

    $uploadResp = $this->postJson('/api/v1/taobao/upload-image', [
        'file' => UploadedFile::fake()->image('shoe.jpg'),
    ])->assertOk();

    expect($uploadResp->json('data.imageHandle'))->toBe('handle_xyz_999')
        ->and($uploadResp->json('data.imageId'))->toBe('handle_xyz_999');

    $this->postJson('/api/v1/taobao/image-search', [
        'image_handle' => 'handle_xyz_999',
        'lang' => 'ru',
    ])->assertOk()
        ->assertJsonPath('data.items.0.id', 'tb_1');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openapi.elim.asia/v1/products/search-by-image'
            && $request['imageHandle'] === 'handle_xyz_999'
            && $request['lang'] === 'ru';
    });
});

it('queries Taobao domestic shipping fee and URL unshortener endpoints', function () {
    Setting::create([
        'key' => ElimWarehouseAddress::SETTING_KEY,
        'value' => json_encode([
            'name' => 'Warehouse Contact',
            'phone' => '02812345678',
            'mobile' => '13800138000',
            'province' => '广东省',
            'city' => '广州市',
            'area' => '白云区',
            'address' => '机场路100号',
        ]),
    ]);

    Http::fake([
        'https://openapi.elim.asia/v2/products/shipping-fee' => Http::response([
            'success' => true,
            'data' => [
                'shipping_fee' => 8.0,
                'currency' => 'CNY',
            ],
        ], 200),
        'https://openapi.elim.asia/v1/utils/unshorten*' => Http::response([
            'success' => true,
            'data' => [
                'original_url' => 'https://m.tb.cn/h.5xyz',
                'resolved_url' => 'https://item.taobao.com/item.htm?id=55667788',
                'platform' => 'taobao',
                'product_id' => '55667788',
            ],
        ], 200),
    ]);

    $this->postJson('/api/v1/taobao/shipping-fee', [
        'id' => '55667788',
    ])->assertOk()
        ->assertJsonPath('data.shipping_fee', 8);

    $this->getJson('/api/v1/utils/unshorten?url='.urlencode('https://m.tb.cn/h.5xyz'))
        ->assertOk()
        ->assertJsonPath('data.product_id', '55667788')
        ->assertJsonPath('data.platform', 'taobao');
});

it('parses official Elim preview fields and forwards preview_token, product_ref, and 1688 spec_id to order creation', function () {
    Setting::create([
        'key' => ElimWarehouseAddress::SETTING_KEY,
        'value' => json_encode([
            'name' => 'Warehouse Contact',
            'phone' => '02812345678',
            'mobile' => '13800138000',
            'province' => '广东省',
            'city' => '广州市',
            'area' => '白云区',
            'address' => '机场路100号',
        ]),
    ]);

    $customer = User::factory()->create();
    UserWallet::create(['user_id' => $customer->id, 'balance' => 2000.00, 'currency' => 'TJS']);

    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-01',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000000',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Main Street 1',
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    UserAddress::create([
        'user_id' => $customer->id,
        'full_name' => 'Test User',
        'phone' => '+992900000001',
        'address_line_1' => 'Customer Street 5',
        'city' => 'Dushanbe',
        'state' => 'Dushanbe',
        'country' => 'Tajikistan',
        'postal_code' => '734000',
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'is_default' => true,
    ]);

    $shippingMethod = ShippingMethod::create([
        'name' => 'Road Express',
        'code' => 'road_express',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $shippingMethod->id,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $customer->forceFill(['warehouse_id' => $warehouse->id])->save();

    $cartItem = UserCartItem::create([
        'user_id' => $customer->id,
        'platform' => '1688',
        'product_id' => '99887766',
        'marketplace_id' => '99887766',
        'sku_id' => 'sku_99',
        'quantity' => 10,
        'unit_price' => 20.00,
        'final_amount_tjs' => 35.00,
        'product_snapshot' => [
            'title' => 'Wholesale Valve',
            'sku_id' => 'sku_99',
            'spec_id' => 'spec_1688_real_id',
        ],
        'synced_at' => now(),
    ]);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/preview' => Http::response([
            'success' => true,
            'data' => [
                'total_product_amount' => 200.00,
                'total_post_fee' => 15.00,
                'total_amount' => 215.00,
                'preview_token' => 'prev_tok_abc123',
                'shops' => [['shop_id' => 's1']],
                'unavailable_items' => [],
            ],
        ], 200),
        'https://openapi.elim.asia/v1/orders' => Http::response([
            'success' => true,
            'data' => [
                'order_id' => 'ORD202611030001',
                'status' => 'pending_payment',
            ],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/wallet*' => Http::response([
            'data' => ['available_balance' => 5000.00],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/orders/ORD202611030001/confirm' => Http::response([
            'data' => ['payment_status' => 'paid', 'status' => 'processing'],
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD202611030001' => Http::response([
            'data' => [
                'id' => 'ORD202611030001',
                'status' => 'ordered',
                'payment_status' => 'paid',
                'goods_amount' => 200.00,
                'domestic_shipping' => 15.00,
                'total_amount' => 215.00,
            ],
        ], 200),
    ]);

    Sanctum::actingAs($customer);

    $previewResp = $this->postJson('/api/v1/auth/1688/cart/preview', [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $shippingMethod->id,
        'payment_method' => 'wallet',
    ])->assertOk();

    expect((float) $previewResp->json('data.goods_subtotal_cny'))->toBe(200.0)
        ->and((float) $previewResp->json('data.shipping_fee_cny'))->toBe(15.0)
        ->and($previewResp->json('data.preview_token'))->toBe('prev_tok_abc123');

    $this->postJson('/api/v1/auth/1688/cart/checkout', [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $shippingMethod->id,
        'payment_method' => 'wallet',
        'idempotency_key' => 'idem_key_1001',
        'client_order_id' => 'CARGO-1001',
    ])->assertCreated();

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://openapi.elim.asia/v1/orders') {
            return false;
        }

        $firstProduct = data_get($request->data(), 'line_items.0', []);

        return $request['preview_token'] === 'prev_tok_abc123'
            && $request['idempotency_key'] === 'idem_key_1001'
            && $request['client_order_id'] === 'CARGO-1001'
            && ($firstProduct['product_ref'] ?? null) === '99887766'
            && ($firstProduct['sku_ref'] ?? null) === 'spec_1688_real_id';
    });

    $createdOrder = CustomerOrder::query()->where('elim_order_id', 'ORD202611030001')->firstOrFail();
    expect((float) $createdOrder->customer_total_cny)->toBe(225.0);
});

it('supports request-payment, cancel-payment-request with wallet refund, payment detail, refresh, and logistics for 1688 and Taobao', function () {
    $customer = User::factory()->create();
    UserWallet::create(['user_id' => $customer->id, 'balance' => 500.00, 'currency' => 'TJS']);
    $walletService = app(WalletService::class);

    $order = createElimOrderForUser($customer, [
        'platform' => '1688',
        'elim_order_id' => 'ORD_1688_900',
        'status' => OrderStatus::CODE_PAID,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_UNPAID,
        'customer_total_cny' => 110.00,
        'customer_total_tjs' => 180.00,
        'final_amount_tjs' => 180.00,
    ]);

    Http::fake([
        'https://openapi.elim.asia/v1/purchasing/wallet/transactions*' => Http::response([
            'data' => [
                'items' => [['id' => 'tx_1', 'type' => 'order_payment', 'amount' => -110]],
            ],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/wallet*' => Http::response([
            'data' => ['available_balance' => 2000.00],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/orders/ORD_1688_900/request-payment' => Http::response([
            'data' => ['payment_status' => 'requested', 'status' => 'pending_payment'],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/orders/ORD_1688_900/payment' => Http::response([
            'data' => ['order_id' => 'ORD_1688_900', 'payment_status' => 'requested', 'amount' => 110.00],
        ], 200),
        'https://openapi.elim.asia/v1/purchasing/orders/ORD_1688_900/cancel-payment-request' => Http::response([
            'data' => ['payment_status' => 'unpaid'],
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD_1688_900/refresh' => Http::response([
            'data' => [
                'id' => 'ORD_1688_900',
                'status' => 'in_transit',
                'payment_status' => 'paid',
                'tracking_numbers' => ['SF999888777'],
            ],
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD_1688_900/logistic-detail*' => Http::response([
            'data' => [
                'tracking_number' => 'SF999888777',
                'steps' => [['time' => '2026-11-03 10:00:00', 'desc' => 'Arrived at hub']],
            ],
        ], 200),
    ]);

    Sanctum::actingAs($customer);

    // 1. Request payment
    $this->postJson("/api/v1/auth/orders/{$order->id}/request-payment")
        ->assertOk()
        ->assertJsonPath('data.payment_status', CustomerOrder::PAYMENT_STATUS_REQUESTED);

    expect($walletService->getBalance($customer))->toBe(320.00);

    // 2. Payment detail
    $this->getJson("/api/v1/auth/orders/{$order->id}/payment")
        ->assertOk()
        ->assertJsonPath('data.payment_status', 'requested');

    // 3. Cancel pending payment request (refunds TJS to customer wallet)
    $this->postJson("/api/v1/auth/orders/{$order->id}/cancel-payment-request")
        ->assertOk()
        ->assertJsonPath('data.payment_status', CustomerOrder::PAYMENT_STATUS_UNPAID);

    expect($walletService->getBalance($customer))->toBe(500.00);

    // 4. Force refresh order from China marketplace
    $this->postJson("/api/v1/auth/orders/{$order->id}/refresh")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::CODE_IN_TRANSIT)
        ->assertJsonPath('data.parcel_tracking_id', 'SF999888777');

    // 5. Domestic logistics for 1688 with package_id & lang
    $this->getJson("/api/v1/auth/orders/{$order->id}/logistics?package_id=98765&lang=en")
        ->assertOk()
        ->assertJsonPath('data.logistics.tracking_number', 'SF999888777');

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://openapi.elim.asia/v1/orders/ORD_1688_900/logistic-detail')
            && $request->data()['package_id'] == '98765'
            && $request->data()['lang'] === 'en';
    });

    // 6. Purchasing wallet transactions
    $this->getJson('/api/v1/auth/orders/elim/purchasing-wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.items.0.id', 'tx_1');
});

it('verifies Elim HMAC-SHA256 webhooks, processes order.updated & payment.updated with automatic customer TJS refund, and blocks replay/out-of-order deliveries', function () {
    $secret = 'whsec_test_secret_key_999';
    Setting::setValue(ElimApiConfig::SETTING_WEBHOOK_SECRET, $secret);
    app(ElimApiConfig::class)->refreshAfterSettingsUpdate();

    $customer = User::factory()->create();
    UserWallet::create(['user_id' => $customer->id, 'balance' => 50.00, 'currency' => 'TJS']);
    $walletService = app(WalletService::class);

    $order = createElimOrderForUser($customer, [
        'platform' => 'taobao',
        'elim_order_id' => 'ORD_WH_100',
        'status' => OrderStatus::CODE_PAID,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_WALLET,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_PAID,
        'paid_at' => now(),
        'customer_total_tjs' => 160.00,
        'final_amount_tjs' => 160.00,
    ]);

    // 1. Invalid signature -> 401
    $badPayload = json_encode(['event' => 'order.updated', 'data' => ['order' => ['id' => 'ORD_WH_100']]]);
    $this->call('POST', '/api/v1/webhooks/elim', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ELIM_TIMESTAMP' => (string) time(),
        'HTTP_X_ELIM_SIGNATURE' => 'sha256=invalid_hash',
    ], $badPayload)->assertStatus(401);

    // 2. Expired timestamp (> 300s) -> 401
    $oldTs = (string) (time() - 600);
    $oldSig = 'sha256='.hash_hmac('sha256', "{$oldTs}.{$badPayload}", $secret);
    $this->call('POST', '/api/v1/webhooks/elim', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ELIM_TIMESTAMP' => $oldTs,
        'HTTP_X_ELIM_SIGNATURE' => $oldSig,
    ], $badPayload)->assertStatus(401);

    // 3. Valid `order.updated` transitioning to `shipped` with tracking number
    $ts1 = (string) time();
    $body1 = json_encode([
        'event' => 'order.updated',
        'timestamp' => '2026-11-03T12:00:00Z',
        'data' => [
            'order' => [
                'id' => 'ORD_WH_100',
                'status' => 'shipped',
                'payment_status' => 'paid',
                'tracking_numbers' => ['YT123456789'],
                'updated_at' => '2026-11-03T12:00:00Z',
            ],
        ],
    ]);
    $sig1 = 'sha256='.hash_hmac('sha256', "{$ts1}.{$body1}", $secret);

    $this->call('POST', '/api/v1/webhooks/elim', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ELIM_TIMESTAMP' => $ts1,
        'HTTP_X_ELIM_SIGNATURE' => $sig1,
        'HTTP_X_ELIM_EVENT' => 'order.updated',
    ], $body1)
        ->assertOk()
        ->assertJsonPath('data.processed', true);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CODE_IN_TRANSIT)
        ->and($order->parcel_tracking_id)->toBe('YT123456789');

    // 4. Out-of-order stale webhook delivery (older `updated_at`) is skipped
    $bodyStale = json_encode([
        'event' => 'order.updated',
        'data' => [
            'order' => [
                'id' => 'ORD_WH_100',
                'status' => 'pending',
                'updated_at' => '2026-11-03T11:00:00Z',
            ],
        ],
    ]);
    $sigStale = 'sha256='.hash_hmac('sha256', "{$ts1}.{$bodyStale}", $secret);

    $this->call('POST', '/api/v1/webhooks/elim', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ELIM_TIMESTAMP' => $ts1,
        'HTTP_X_ELIM_SIGNATURE' => $sigStale,
    ], $bodyStale)
        ->assertOk()
        ->assertJsonPath('data.skipped', true)
        ->assertJsonPath('data.reason', 'stale_webhook_delivery');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CODE_IN_TRANSIT);

    // 5. Valid `payment.updated` with `payment_status = refunded` & `status = cancelled` -> auto-refunds customer TJS wallet once
    $bodyRefund = json_encode([
        'event' => 'payment.updated',
        'data' => [
            'order' => [
                'id' => 'ORD_WH_100',
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'updated_at' => '2026-11-03T12:05:00Z',
            ],
        ],
    ]);
    $sigRefund = 'sha256='.hash_hmac('sha256', "{$ts1}.{$bodyRefund}", $secret);

    $this->call('POST', '/api/v1/webhooks/elim', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_ELIM_TIMESTAMP' => $ts1,
        'HTTP_X_ELIM_SIGNATURE' => $sigRefund,
        'HTTP_X_ELIM_EVENT' => 'payment.updated',
    ], $bodyRefund)
        ->assertOk()
        ->assertJsonPath('data.refunded', true);

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::CODE_CANCELLED)
        ->and($order->payment_status)->toBe(CustomerOrder::PAYMENT_STATUS_REFUNDED)
        ->and($order->cancelled_at)->not->toBeNull()
        ->and($order->cancellation_refund_transaction_id)->not->toBeNull()
        ->and($walletService->getBalance($customer))->toBe(210.00)
        ->and(WalletTransaction::where('user_id', $customer->id)->where('source', WalletTransaction::SOURCE_ORDER_REFUND)->count())->toBe(1)
        ->and(ElimApiLog::where('source', ElimApiLog::SOURCE_WEBHOOK)->count())->toBe(3);
});

it('manages Elim webhooks via ElimWebhookService and Admin OrderDetailPage actions', function () {
    Http::fake([
        'https://openapi.elim.asia/v1/webhooks' => Http::response([
            'success' => true,
            'data' => [
                'id' => 'wh_live_01',
                'url' => url('/api/v1/webhooks/elim'),
                'secret' => 'whsec_generated_123',
                'events' => ['order.updated', 'payment.updated'],
                'is_active' => true,
            ],
        ], 201),
        'https://openapi.elim.asia/v1/webhooks/wh_live_01/rotate-secret' => Http::response([
            'success' => true,
            'data' => [
                'id' => 'wh_live_01',
                'secret' => 'whsec_rotated_456',
            ],
        ], 200),
        'https://openapi.elim.asia/v1/webhooks/wh_live_01/test' => Http::response([
            'success' => true,
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD_STAFF_1/refresh' => Http::response([
            'data' => [
                'id' => 'ORD_STAFF_1',
                'status' => 'completed',
                'payment_status' => 'paid',
            ],
        ], 200),
    ]);

    $webhookService = app(ElimWebhookService::class);
    $created = $webhookService->create(url('/api/v1/webhooks/elim'));
    app(ElimApiConfig::class)->clearCache();
    expect(data_get($created, 'data.id'))->toBe('wh_live_01')
        ->and(app(ElimApiConfig::class)->webhookSecret())->toBe('whsec_generated_123');

    $webhookService->rotateSecret('wh_live_01');
    app(ElimApiConfig::class)->clearCache();
    expect(app(ElimApiConfig::class)->webhookSecret())->toBe('whsec_rotated_456');

    $admin = Admin::create([
        'name' => 'Super Admin',
        'username' => 'superadmin',
        'email' => 'super@example.com',
        'password' => Hash::make('secret'),
        'role' => Admin::ROLE_SUPER_ADMIN,
        'email_verified_at' => now(),
    ]);

    $customer = User::factory()->create();

    $order = createElimOrderForUser($customer, [
        'platform' => 'taobao',
        'elim_order_id' => 'ORD_STAFF_1',
        'status' => OrderStatus::CODE_IN_TRANSIT,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_PAID,
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(OrderDetailPage::class, ['order' => $order])
        ->call('refreshElimOrder');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CODE_DELIVERED);
});

it('auto-refunds customer TJS wallet when sync or refresh detects upstream cancellation/refund or payment rejection, and prevents status regression', function () {
    $customer = User::factory()->create();
    UserWallet::create(['user_id' => $customer->id, 'balance' => 100.00, 'currency' => 'TJS']);
    $walletService = app(WalletService::class);

    $paidOrder = createElimOrderForUser($customer, [
        'platform' => 'taobao',
        'elim_order_id' => 'ORD_SYNC_REFUND_1',
        'status' => OrderStatus::CODE_PAID,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_PAID,
        'paid_at' => now(),
        'customer_total_tjs' => 150.00,
        'final_amount_tjs' => 150.00,
    ]);

    $inTransitOrder = createElimOrderForUser($customer, [
        'platform' => 'taobao',
        'elim_order_id' => 'ORD_NO_REGRESS_1',
        'status' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_PAID,
        'paid_at' => now(),
    ]);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/ORD_SYNC_REFUND_1' => Http::response([
            'data' => [
                'id' => 'ORD_SYNC_REFUND_1',
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'updated_at' => '2026-11-03T15:00:00Z',
            ],
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD_NO_REGRESS_1' => Http::response([
            'data' => [
                'id' => 'ORD_NO_REGRESS_1',
                'status' => 'paid',
                'payment_status' => 'paid',
                'updated_at' => '2026-11-03T15:00:00Z',
            ],
        ], 200),
    ]);

    Sanctum::actingAs($customer);

    // Syncing a paid order that was cancelled/refunded upstream credits customer wallet once
    $this->postJson("/api/v1/auth/orders/{$paidOrder->id}/sync")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::CODE_CANCELLED)
        ->assertJsonPath('data.payment_status', CustomerOrder::PAYMENT_STATUS_REFUNDED);

    $paidOrder->refresh();
    expect($paidOrder->cancellation_refund_transaction_id)->not->toBeNull()
        ->and($paidOrder->cancelled_at)->not->toBeNull()
        ->and($walletService->getBalance($customer))->toBe(250.00);

    // Syncing an order already in shipped_to_tajikistan with Elim status=paid does not regress status
    $this->postJson("/api/v1/auth/orders/{$inTransitOrder->id}/sync")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN);
});

it('applies by_volume tiered pricing by quantity in cart, blocks duplicate pay on requested orders, and accepts 20-digit or string logistics package_id', function () {
    $customer = User::factory()->create();
    UserWallet::create(['user_id' => $customer->id, 'balance' => 500.00, 'currency' => 'TJS']);

    Http::fake([
        'https://openapi.elim.asia/v2/products/find' => Http::response([
            'data' => [
                'id' => '777888',
                'mp_id' => '777888',
                'title' => 'Industrial Sensor',
                'status' => 'active',
                'price' => 100,
                'quote_type' => 'by_volume',
                'price_range' => [
                    ['begin_amount' => 1, 'price' => 100],
                    ['begin_amount' => 10, 'price' => 85],
                ],
                'skus' => [],
            ],
        ], 200),
        'https://openapi.elim.asia/v1/orders/ORD_LOG_1/logistic-detail*' => Http::response([
            'data' => [
                'tracking_number' => 'SF1234567890',
                'steps' => [['desc' => 'In transit']],
            ],
        ], 200),
    ]);

    Sanctum::actingAs($customer);

    // Quantity 2 -> tier 1 (100 CNY)
    $addResp = $this->postJson('/api/v1/auth/1688/cart/items', [
        'product_id' => '777888',
        'sku_id' => '',
        'quantity' => 2,
        'final_amount' => 150,
    ])->assertCreated();

    $cartItemId = $addResp->json('data.items.0.id');
    expect((float) UserCartItem::findOrFail($cartItemId)->unit_price)->toBe(100.0);

    // Update quantity to 10 -> tier 2 (85 CNY)
    $this->patchJson("/api/v1/auth/1688/cart/items/{$cartItemId}", [
        'quantity' => 10,
        'final_amount' => 130,
    ])->assertOk();

    expect((float) UserCartItem::findOrFail($cartItemId)->unit_price)->toBe(85.0);

    // Order in `requested` payment_status rejects duplicate `/pay` call
    $requestedOrder = createElimOrderForUser($customer, [
        'platform' => 'taobao',
        'elim_order_id' => 'ORD_LOG_1',
        'payment_status' => CustomerOrder::PAYMENT_STATUS_REQUESTED,
    ]);

    $this->postJson("/api/v1/auth/orders/{$requestedOrder->id}/pay")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['payment']);

    // 20-digit Taobao package_id and string tracking number in logistics
    $this->getJson("/api/v1/auth/orders/{$requestedOrder->id}/logistics?package_id=12345678901234567890&lang=ru")
        ->assertOk()
        ->assertJsonPath('data.package_id', '12345678901234567890');
});
