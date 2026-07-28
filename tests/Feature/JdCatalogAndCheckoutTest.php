<?php

use App\Models\CurrencyExchangeRate;
use App\Models\Platform;
use App\Models\User;
use App\Models\UserCartItem;
use App\Models\Warehouse;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\Admin;
use App\Services\Wallet\WalletService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);

    config([
        'services.rapidapi.key' => 'test-rapidapi-key',
        'services.rapidapi.host' => 'china-e-commerce-data-api.p.rapidapi.com',
        'services.rapidapi.base_url' => 'https://china-e-commerce-data-api.p.rapidapi.com',
        'services.exchange_rate.default_rate' => 1.5,
    ]);

    CurrencyExchangeRate::query()->create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 1.5,
        'last_synced_at' => now(),
    ]);

    Platform::create([
        'code' => 'jd',
        'name' => ['en' => 'JD.com'],
        'logo' => [],
        'is_available' => true,
    ]);

    \App\Models\Setting::create([
        'key' => \App\Support\Elim\ElimWarehouseAddress::SETTING_KEY,
        'value' => json_encode([
            'name' => 'China WH',
            'phone' => '02812345678',
            'mobile' => '13800138000',
            'address' => '广州市天河区体育西路123号',
            'province' => '广东省',
            'city' => '广州市',
            'area' => '天河区',
        ]),
    ]);
});

function jdDetailFakeBody(): array
{
    return [
        'ok' => true,
        'operation' => 'productDetail',
        'count' => 1,
        'data' => [[
            'itemId' => '100256400499',
            'productTitle' => 'HUAWEI Pura 90 Pro',
            'coverUrl' => 'https://img.example/cover.jpg',
            'priceMasked' => true,
            'priceDisplay' => '6??9',
            'stockQuantity' => '25',
            'shopId' => '1000004259',
            'shopName' => '华为京东自营旗舰店',
            'color' => '桑果黑',
            'size' => '16GB+512GB',
            'categoryIds' => ['9987', '653', '655'],
            'itemUrl' => 'https://item.jd.com/100256400499.html',
            'status' => 'success',
        ]],
    ];
}

function jdPriceFakeBody(): array
{
    return [
        'ok' => true,
        'operation' => 'productPrice',
        'count' => 1,
        'data' => [[
            'itemId' => '100256400499',
            'price' => 6499,
            'currency' => 'CNY',
            'available' => true,
        ]],
    ];
}

function jdSearchFakeBody(): array
{
    return [
        'ok' => true,
        'operation' => 'productSearch',
        'count' => 1,
        'data' => [[
            'itemId' => '100256400499',
            'productTitle' => 'HUAWEI Pura 90 Pro',
            'coverUrl' => 'https://img.example/cover.jpg',
            'price' => 6499,
            'itemUrl' => 'https://item.jd.com/100256400499.html',
            'shopName' => '华为京东自营旗舰店',
            'sellerType' => 1,
            'isJdSelf' => true,
            'salesText' => '超千人购买',
            '_page' => 1,
            '_totalCount' => 10,
            'status' => 'success',
        ]],
    ];
}

function fakeJdHttp(): void
{
    Http::fake([
        'china-e-commerce-data-api.p.rapidapi.com/china-ecommerce/jd-search*' => Http::response(jdSearchFakeBody(), 200),
        'china-e-commerce-data-api.p.rapidapi.com/china-ecommerce/jd-detail*' => Http::response(jdDetailFakeBody(), 200),
        'china-e-commerce-data-api.p.rapidapi.com/china-ecommerce/jd-price*' => Http::response(jdPriceFakeBody(), 200),
    ]);
}

it('lists and shows jd products for guests via rapidapi', function () {
    fakeJdHttp();

    $this->getJson('/api/v1/jd/search?q=华为手机&page=1')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.platform', 'jd')
        ->assertJsonPath('data.items.0.id', '100256400499')
        ->assertJsonPath('data.items.0.price', 6499);

    $this->getJson('/api/v1/public/jd/products/100256400499')
        ->assertOk()
        ->assertJsonPath('data.platform', 'jd')
        ->assertJsonPath('data.id', '100256400499')
        ->assertJsonPath('data.price', 6499)
        ->assertJsonPath('data.images.0', 'https://img.example/cover.jpg')
        ->assertJsonPath('data.skus.0.id', '100256400499');
});

it('returns 501 for unsupported jd image search', function () {
    $this->postJson('/api/v1/jd/image-search', [
        'img_url' => 'https://example.com/a.jpg',
    ])->assertStatus(501);
});

it('adds jd product to wishlist', function () {
    fakeJdHttp();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/wishlist', [
        'platform' => 'jd',
        'product_id' => '100256400499',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('user_wishlist_items', [
        'user_id' => $user->id,
        'platform' => 'jd',
        'product_id' => '100256400499',
    ]);
});

it('adds jd cart item and places local checkout without elim http', function () {
    fakeJdHttp();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-JD',
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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo-jd',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $user->forceFill(['warehouse_id' => $warehouse->id])->save();

    app(WalletService::class)->adminAddFunds(
        $user,
        20000,
        'Seed',
        Admin::create([
            'name' => 'A',
            'username' => 'ajd',
            'email' => 'ajd@test.com',
            'password' => bcrypt('x'),
        ])
    );

    $this->postJson('/api/v1/auth/jd/cart/items', [
        'product_id' => '100256400499',
        'quantity' => 1,
        'final_amount' => 100.00,
    ])
        ->assertCreated()
        ->assertJsonPath('data.platform', 'jd')
        ->assertJsonPath('data.items.0.product_id', '100256400499');

    $cartItem = UserCartItem::query()->where('user_id', $user->id)->where('platform', 'jd')->first();
    expect($cartItem)->not->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'jd-detail'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'openapi.elim.asia'));

    $this->postJson('/api/v1/auth/jd/cart/preview', [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $method->id,
        'payment_method' => 'wallet',
    ])
        ->assertOk()
        ->assertJsonPath('data.platform', 'jd')
        ->assertJsonPath('data.demo_mode', true);

    $this->postJson('/api/v1/auth/jd/cart/checkout', [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $method->id,
        'payment_method' => 'wallet',
    ])
        ->assertCreated()
        ->assertJsonPath('data.platform', 'jd')
        ->assertJsonPath('data.payment_status', 'paid');

    $this->assertDatabaseHas('customer_orders', [
        'user_id' => $user->id,
        'platform' => 'jd',
        'is_demo_order' => true,
        'payment_status' => 'paid',
    ]);

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/orders'));
});
