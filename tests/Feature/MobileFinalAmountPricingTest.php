<?php

use App\Models\CustomerOrder;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\UserCartItem;
use App\Models\Warehouse;
use App\Services\Cart\Taobao\TaobaoCartService;
use App\Services\Cart\Taobao\TaobaoOrderService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    config(['services.elim.demo_mode' => true]);

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

it('returns checkout preview with final_amount equal to cart line sum', function () {
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

    UserCartItem::create([
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

    $preview = app(TaobaoOrderService::class)->preview($user, [
        'shipping_method_id' => $method->id,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
    ]);

    expect($preview['final_amount'])->toBe(175.25)
        ->and($preview)->not->toHaveKey('commission')
        ->and($preview)->not->toHaveKey('customer_total_tjs');
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
