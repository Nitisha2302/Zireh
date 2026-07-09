<?php

use App\Http\Resources\Api\V1\Cart\Taobao\TaobaoCartItemResource;
use App\Models\CustomerOrder;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserCartItem;
use App\Models\Warehouse;
use App\Services\Cart\Taobao\TaobaoCartService;
use App\Services\Cart\Taobao\TaobaoOrderService;
use App\Services\Elim\TaobaoService;
use App\Services\Wallet\WalletService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    config(['services.elim.demo_mode' => true]);

    \App\Models\Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao'],
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

it('requires final_amount when adding a cart item via api', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/taobao/cart/items', [
        'product_id' => '123',
        'sku_id' => 'sku1',
        'quantity' => 1,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['final_amount']);
});

it('returns cart summary with summed final_amount', function () {
    $user = User::factory()->create();

    UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p1',
        'marketplace_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 1,
        'unit_price' => 10,
        'final_amount_tjs' => 120.50,
        'product_snapshot' => ['title' => 'A'],
        'synced_at' => now(),
    ]);

    UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p2',
        'marketplace_id' => 'p2',
        'sku_id' => 's2',
        'quantity' => 2,
        'unit_price' => 15,
        'final_amount_tjs' => 229.50,
        'product_snapshot' => ['title' => 'B'],
        'synced_at' => now(),
    ]);

    $cart = app(TaobaoCartService::class)->getCart($user);

    expect($cart['summary']['final_amount'])->toBe(350.0)
        ->and($cart['summary'])->not->toHaveKey('subtotal')
        ->and($cart['summary'])->not->toHaveKey('commission');
});

it('returns checkout preview with final_amount for a single cart line', function () {
    $user = User::factory()->create();

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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    $user->forceFill(['warehouse_id' => $warehouse->id])->save();

    $cartItem = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => '123',
        'marketplace_id' => '123',
        'sku_id' => 'sku1',
        'quantity' => 1,
        'unit_price' => 20,
        'final_amount_tjs' => 175.25,
        'product_snapshot' => ['title' => 'Test'],
        'synced_at' => now(),
    ]);

    UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => '456',
        'marketplace_id' => '456',
        'sku_id' => 'sku2',
        'quantity' => 1,
        'unit_price' => 30,
        'final_amount_tjs' => 99.75,
        'product_snapshot' => ['title' => 'Other'],
        'synced_at' => now(),
    ]);

    $preview = app(TaobaoOrderService::class)->preview($user, [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
    ]);

    expect($preview['final_amount'])->toBe(175.25)
        ->and($preview['cart_item_id'])->toBe($cartItem->id)
        ->and($preview['items'])->toHaveCount(1)
        ->and($preview)->not->toHaveKey('commission')
        ->and($preview)->not->toHaveKey('customer_total_tjs');
});

it('requires cart_item_id on checkout preview via api', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/taobao/cart/preview', [
        'shipping_method_id' => 1,
        'payment_method' => 'online',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['cart_item_id']);
});

it('checks out one cart line and leaves other lines in cart', function () {
    $user = User::factory()->create();

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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    $user->forceFill(['warehouse_id' => $warehouse->id])->save();

    $lineOne = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p1',
        'marketplace_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 1,
        'unit_price' => 10,
        'final_amount_tjs' => 120.50,
        'product_snapshot' => ['title' => 'A'],
        'synced_at' => now(),
    ]);

    $lineTwo = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p2',
        'marketplace_id' => 'p2',
        'sku_id' => 's2',
        'quantity' => 1,
        'unit_price' => 15,
        'final_amount_tjs' => 229.50,
        'product_snapshot' => ['title' => 'B'],
        'synced_at' => now(),
    ]);

    $order = app(TaobaoOrderService::class)->checkout($user, [
        'cart_item_id' => $lineOne->id,
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
    ]);

    expect((float) $order->final_amount_tjs)->toBe(120.5)
        ->and(UserCartItem::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(UserCartItem::query()->find($lineTwo->id))->not->toBeNull();

    $secondOrder = app(TaobaoOrderService::class)->checkout($user, [
        'cart_item_id' => $lineTwo->id,
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
    ]);

    expect((float) $secondOrder->final_amount_tjs)->toBe(229.5)
        ->and(UserCartItem::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and($secondOrder->id)->not->toBe($order->id);
});

it('creates one order for a single line with quantity greater than one', function () {
    $user = User::factory()->create();

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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    $user->forceFill(['warehouse_id' => $warehouse->id])->save();

    $cartItem = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p1',
        'marketplace_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 2,
        'unit_price' => 10,
        'final_amount_tjs' => 240.00,
        'product_snapshot' => ['title' => 'A'],
        'synced_at' => now(),
    ]);

    $order = app(TaobaoOrderService::class)->checkout($user, [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
    ]);

    expect((float) $order->final_amount_tjs)->toBe(240.0)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2);
});

it('rejects checkout for another users cart item', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $cartItem = UserCartItem::create([
        'user_id' => $owner->id,
        'platform' => 'taobao',
        'product_id' => 'p1',
        'marketplace_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 1,
        'unit_price' => 10,
        'final_amount_tjs' => 100,
        'product_snapshot' => ['title' => 'A'],
        'synced_at' => now(),
    ]);

    expect(fn () => app(TaobaoCartService::class)->resolveCartItem($other, $cartItem->id))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

function mockTaobaoProductDetail(): array
{
    return [
        'id' => '123',
        'platform' => 'taobao',
        'marketplace_id' => '123',
        'status' => 'available',
        'price' => 20,
        'skus' => [],
    ];
}

function bindMockTaobaoService(): void
{
    $mock = Mockery::mock(TaobaoService::class);
    $mock->shouldReceive('find')->andReturn(mockTaobaoProductDetail());
    app()->instance(TaobaoService::class, $mock);
}

it('stores line total as per-unit final_amount multiplied by quantity on add', function () {
    bindMockTaobaoService();

    $user = User::factory()->create();

    $result = app(TaobaoCartService::class)->add($user, [
        'product_id' => '123',
        'sku_id' => '',
        'quantity' => 2,
        'final_amount' => 100,
    ]);

    expect((float) $result['item']->final_amount_tjs)->toBe(200.0)
        ->and($result['item']->quantity)->toBe(2);
});

it('recalculates line total when quantity is updated', function () {
    bindMockTaobaoService();

    $user = User::factory()->create();
    $cartItem = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => '123',
        'marketplace_id' => '123',
        'sku_id' => '',
        'quantity' => 1,
        'unit_price' => 20,
        'final_amount_tjs' => 100,
        'product_snapshot' => ['title' => 'Test'],
        'synced_at' => now(),
    ]);

    app(TaobaoCartService::class)->update($user, $cartItem, [
        'quantity' => 2,
        'final_amount' => 100,
    ]);

    expect((float) $cartItem->fresh()->final_amount_tjs)->toBe(200.0)
        ->and($cartItem->fresh()->quantity)->toBe(2);
});

it('exposes final_amount_per_unit and line total in cart item resource', function () {
    $item = UserCartItem::make([
        'product_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 2,
        'final_amount_tjs' => 200,
        'product_snapshot' => ['title' => 'A'],
    ]);
    $item->id = 1;

    $payload = (new TaobaoCartItemResource($item))->resolve();

    expect($payload['final_amount_per_unit'])->toBe(100.0)
        ->and($payload['final_amount'])->toBe(200.0);
});

it('debits wallet for quantity-scaled line total on checkout', function () {
    $user = User::factory()->create();

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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    $user->forceFill(['warehouse_id' => $warehouse->id])->save();

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

    $cartItem = UserCartItem::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'product_id' => 'p1',
        'marketplace_id' => 'p1',
        'sku_id' => 's1',
        'quantity' => 2,
        'unit_price' => 10,
        'final_amount_tjs' => 200.00,
        'product_snapshot' => ['title' => 'A'],
        'synced_at' => now(),
    ]);

    $order = app(TaobaoOrderService::class)->checkout($user, [
        'cart_item_id' => $cartItem->id,
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_WALLET,
    ]);

    expect((float) $order->final_amount_tjs)->toBe(200.0)
        ->and((float) app(WalletService::class)->getBalance($user))->toBe(300.0);
});

it('exposes final_amount only in order api response', function () {
    $user = User::factory()->create();
    $order = CustomerOrder::create([
        'user_id' => $user->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-DEMO-1',
        'status' => \App\Models\OrderStatus::CODE_PAID,
        'payment_status' => 'paid',
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 10,
        'elim_service_fee_cny' => 5,
        'commission_amount' => 3,
        'customer_total_cny' => 118,
        'customer_total_tjs' => 350,
        'final_amount_tjs' => 350,
        'receiver_address' => ['name' => 'WH'],
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/auth/taobao/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.final_amount', 350)
        ->assertJsonMissingPath('data.commission')
        ->assertJsonMissingPath('data.customer_total_tjs')
        ->assertJsonMissingPath('data.goods_subtotal_cny');
});
