<?php

use App\Models\CustomerOrder;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Warehouse;
use App\Services\Wallet\WalletService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    config(['services.elim.base_url' => 'https://openapi.elim.asia']);
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);

    SettingHelperSetElimWarehouse();

    \App\Models\Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao'],
        'logo' => [],
        'is_available' => true,
    ]);

    $platform = \App\Models\Platform::query()->where('code', 'taobao')->first();
    \App\Models\PlatformCommissionSlab::create([
        'platform_id' => $platform->id,
        'min_amount' => 0,
        'max_amount' => 100000,
        'commission_percentage' => 5,
        'is_active' => true,
    ]);
});

function SettingHelperSetElimWarehouse(): void
{
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
}

function makeCheckoutFixtures(User $user): array
{
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

    $address = UserAddress::create([
        'user_id' => $user->id,
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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
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

    return compact('warehouse', 'address', 'method');
}

function checkoutPayload(array $fixtures, string $paymentMethod = 'online'): array
{
    return [
        'warehouse_id' => $fixtures['warehouse']->id,
        'address_id' => $fixtures['address']->id,
        'shipping_method_id' => $fixtures['method']->id,
        'payment_method' => $paymentMethod,
        'weight_kg' => 2,
        'remark' => 'Test checkout',
    ];
}

it('places demo checkout with wallet payment and stores warehouse and address', function () {
    config(['services.elim.demo_mode' => true]);

    $user = User::factory()->create();
    $fixtures = makeCheckoutFixtures($user);

    app(WalletService::class)->adminAddFunds(
        $user,
        500,
        'Seed',
        \App\Models\Admin::create([
            'name' => 'A',
            'username' => 'a',
            'email' => 'a@test.com',
            'password' => bcrypt('x'),
            'email_verified_at' => now(),
        ])
    );

    $service = app(\App\Services\Cart\Taobao\TaobaoOrderService::class);

    // Seed cart item manually
    \App\Models\UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => '123',
        'marketplace_id' => '123',
        'sku_id' => 'sku1',
        'quantity' => 1,
        'unit_price' => 20,
        'product_snapshot' => ['title' => 'Test'],
        'synced_at' => now(),
    ]);

    Http::fake();

    Sanctum::actingAs($user);

    $order = $service->checkout($user, checkoutPayload($fixtures, CustomerOrder::PAYMENT_METHOD_WALLET));

    expect($order->is_demo_order)->toBeTrue()
        ->and($order->warehouse_id)->toBe($fixtures['warehouse']->id)
        ->and($order->user_address_id)->toBe($fixtures['address']->id)
        ->and($order->shipping_method_id)->toBe($fixtures['method']->id)
        ->and($order->payment_method)->toBe(CustomerOrder::PAYMENT_METHOD_WALLET)
        ->and($order->payment_status)->toBe('paid')
        ->and($order->warehouse_snapshot)->toBeArray()
        ->and($order->address_snapshot)->toBeArray()
        ->and($order->cargo_shipping_fee_tjs)->toBeGreaterThan(0);

    Http::assertNothingSent();
});

it('skips wallet deduction for online payment method at checkout', function () {
    config(['services.elim.demo_mode' => true]);

    $user = User::factory()->create();
    $fixtures = makeCheckoutFixtures($user);

    \App\Models\UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => '123',
        'marketplace_id' => '123',
        'sku_id' => 'sku1',
        'quantity' => 1,
        'unit_price' => 20,
        'product_snapshot' => ['title' => 'Test'],
        'synced_at' => now(),
    ]);

    $service = app(\App\Services\Cart\Taobao\TaobaoOrderService::class);
    $order = $service->checkout($user, checkoutPayload($fixtures, CustomerOrder::PAYMENT_METHOD_ONLINE));

    expect($order->payment_method)->toBe(CustomerOrder::PAYMENT_METHOD_ONLINE)
        ->and($order->payment_status)->toBe('unpaid')
        ->and((float) app(WalletService::class)->getBalance($user))->toBe(0.0);
});
