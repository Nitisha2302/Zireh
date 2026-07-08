<?php

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Order\OrderPickupService;
use App\Services\Wallet\WalletService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function makePickupFixtures(): array
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

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $user = User::factory()->create(['warehouse_id' => $warehouse->id]);
    $admin = Admin::create([
        'name' => 'TJ Staff',
        'username' => 'tj_staff',
        'email' => 'tj@example.com',
        'role' => Admin::ROLE_TAJIKISTAN_WAREHOUSE,
        'warehouse_id' => $warehouse->id,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $order = CustomerOrder::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'shipping_method_id' => $method->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD1001',
        'status' => OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
        'payment_status' => 'paid',
        'payment_method' => 'wallet',
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 10,
        'commission_amount' => 5,
        'commission_percentage' => 5,
        'customer_total_cny' => 115,
        'customer_total_tjs' => 115,
        'receiver_address' => ['name' => 'China WH'],
        'warehouse_snapshot' => ['warehouse_name' => $warehouse->warehouse_name],
    ]);

    return compact('warehouse', 'method', 'user', 'admin', 'order');
}

it('confirms pickup shipping and allows wallet payment then delivery', function () {
    $fixtures = makePickupFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 30,
        'package_width_cm' => 30,
        'package_height_cm' => 30,
        'package_weight_kg' => 2,
    ]);

    expect($order->status)->toBe(OrderStatus::CODE_READY_FOR_PICKUP)
        ->and($order->pickup_payment_status)->toBe(CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING)
        ->and($order->pickup_token)->not->toBeNull()
        ->and((float) $order->pickup_shipping_fee_tjs)->toBeGreaterThan(0);

    Sanctum::actingAs($fixtures['user']);
    app(WalletService::class)->adminAddFunds($fixtures['user'], 500, 'Seed', Admin::create([
        'name' => 'A', 'username' => 'a', 'email' => 'a@test.com',
        'password' => Hash::make('x'), 'email_verified_at' => now(),
    ]));

    $this->postJson("/api/v1/auth/orders/{$order->id}/pickup/pay", [
        'payment_method' => 'wallet',
    ])
        ->assertOk()
        ->assertJsonPath('data.pickup.payment_status', CustomerOrder::PICKUP_PAYMENT_STATUS_PAID);

    $delivered = $service->deliver($order->fresh(), $fixtures['admin']);
    expect($delivered->status)->toBe(OrderStatus::CODE_DELIVERED_TO_CUSTOMER);
});

it('returns pickup details with qr payload for ready orders', function () {
    $fixtures = makePickupFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 20,
        'package_width_cm' => 20,
        'package_height_cm' => 20,
        'package_weight_kg' => 3,
    ]);

    Sanctum::actingAs($fixtures['user']);

    $this->getJson("/api/v1/auth/orders/{$order->id}/pickup")
        ->assertOk()
        ->assertJsonPath('data.qr.payload', 'cargo-pickup:'.$order->pickup_token)
        ->assertJsonPath('data.payment_status', CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING);
});

it('includes pickup shipping on taobao order show api after confirm', function () {
    $fixtures = makePickupFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 10,
        'package_width_cm' => 10,
        'package_height_cm' => 10,
        'package_weight_kg' => 20,
    ]);

    Sanctum::actingAs($fixtures['user']);

    $fee = (float) $order->pickup_shipping_fee_tjs;

    $response = $this->getJson("/api/v1/auth/taobao/orders/{$order->id}");

    $response->assertOk()
        ->assertJsonPath('data.pickup.payment_status', CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING)
        ->assertJsonPath('data.pickup.qr.payload', 'cargo-pickup:'.$order->pickup_token);

    expect((float) $response->json('data.cargo_shipping_fee_tjs'))->toBe($fee)
        ->and((float) $response->json('data.pickup.shipping_fee_tjs'))->toBe($fee);
});

it('pays pickup shipping online without deducting wallet balance', function () {
    $fixtures = makePickupFixtures();
    $service = app(OrderPickupService::class);
    $walletService = app(WalletService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 30,
        'package_width_cm' => 30,
        'package_height_cm' => 30,
        'package_weight_kg' => 2,
    ]);

    Sanctum::actingAs($fixtures['user']);

    $balanceBefore = $walletService->getBalance($fixtures['user']);
    $fee = (float) $order->pickup_shipping_fee_tjs;

    $this->postJson("/api/v1/auth/orders/{$order->id}/pickup/pay", [
        'payment_method' => 'online',
    ])
        ->assertOk()
        ->assertJsonPath('data.pickup.payment_status', CustomerOrder::PICKUP_PAYMENT_STATUS_PAID)
        ->assertJsonPath('data.pickup.payment_method', 'online');

    expect($walletService->getBalance($fixtures['user']))->toBe($balanceBefore);

    $order->refresh();
    $transaction = $order->pickupWalletTransaction;

    expect($transaction)->not->toBeNull()
        ->and($transaction->source)->toBe('pickup_shipping_online_payment')
        ->and((float) $transaction->amount)->toBe($fee)
        ->and((float) $transaction->balance_before)->toBe($balanceBefore)
        ->and((float) $transaction->balance_after)->toBe($balanceBefore);
});

it('requires payment_method for pickup pay', function () {
    $fixtures = makePickupFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 20,
        'package_width_cm' => 20,
        'package_height_cm' => 20,
        'package_weight_kg' => 2,
    ]);

    Sanctum::actingAs($fixtures['user']);

    $this->postJson("/api/v1/auth/orders/{$order->id}/pickup/pay", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method']);
});
