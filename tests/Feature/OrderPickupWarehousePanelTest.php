<?php

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Order\OrderPickupService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function makePickupPanelFixtures(): array
{
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-PICKUP',
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

    $admin = Admin::create([
        'name' => 'TJ Staff',
        'username' => 'tj_pickup',
        'email' => 'tj_pickup@example.com',
        'role' => Admin::ROLE_TAJIKISTAN_WAREHOUSE,
        'warehouse_id' => $warehouse->id,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $user = User::factory()->create(['warehouse_id' => $warehouse->id]);

    $order = CustomerOrder::create([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'shipping_method_id' => $method->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-PICKUP',
        'status' => OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
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

    return compact('warehouse', 'method', 'admin', 'user', 'order');
}

it('allows tajikistan staff to confirm pickup from order detail and deliver via pickup page', function () {
    $fixtures = makePickupPanelFixtures();

    $this->actingAs($fixtures['admin'], 'admin');

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\OrderDetailPage::class, ['order' => $fixtures['order']])
        ->set('shippingMethodId', $fixtures['method']->id)
        ->set('packageLengthCm', '40')
        ->set('packageWidthCm', '40')
        ->set('packageHeightCm', '40')
        ->set('packageWeightKg', '2')
        ->call('previewPickupShipping')
        ->assertHasNoErrors()
        ->call('confirmPickup')
        ->assertHasNoErrors();

    $order = $fixtures['order']->fresh();
    expect($order->status)->toBe(OrderStatus::CODE_READY_FOR_PICKUP)
        ->and($order->pickup_token)->not->toBeNull();

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\PickupPage::class, ['token' => $order->pickup_token])
        ->assertSet('order.id', $order->id)
        ->call('markPaymentReceived')
        ->assertHasNoErrors()
        ->call('deliverOrder')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_DELIVERED_TO_CUSTOMER);
});

it('blocks pickup page access for orders outside staff warehouse', function () {
    $fixtures = makePickupPanelFixtures();
    $otherWarehouse = Warehouse::create([
        'warehouse_name' => 'Other Hub',
        'warehouse_code' => 'DUS-OTHER',
        'contact_person' => 'Other',
        'contact_number' => '+992900000099',
        'country' => 'Tajikistan',
        'state' => 'Khujand',
        'city' => 'Khujand',
        'address' => 'Other Street',
        'latitude' => 40.28,
        'longitude' => 69.62,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $otherOrder = CustomerOrder::create([
        'user_id' => $fixtures['user']->id,
        'warehouse_id' => $otherWarehouse->id,
        'shipping_method_id' => $fixtures['method']->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-OTHER',
        'status' => OrderStatus::CODE_READY_FOR_PICKUP,
        'payment_status' => 'paid',
        'payment_method' => 'wallet',
        'goods_subtotal_cny' => 50,
        'customer_total_cny' => 50,
        'customer_total_tjs' => 50,
        'pickup_token' => 'other-token-uuid',
        'pickup_payment_status' => CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING,
        'pickup_shipping_fee_tjs' => 25,
        'receiver_address' => ['name' => 'China WH'],
        'warehouse_snapshot' => ['warehouse_name' => $otherWarehouse->warehouse_name],
    ]);

    $this->actingAs($fixtures['admin'], 'admin')
        ->get(route('tajikistan.pickup.show', ['token' => $otherOrder->pickup_token]))
        ->assertForbidden();
});

it('shows validation error for unknown pickup token', function () {
    $fixtures = makePickupPanelFixtures();

    $this->actingAs($fixtures['admin'], 'admin');

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\PickupPage::class)
        ->set('pickupToken', 'unknown-token')
        ->call('lookupOrder')
        ->assertHasErrors('pickup_token');
});

it('loads order from cargo-pickup qr payload via scanPickupQr', function () {
    $fixtures = makePickupPanelFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 20,
        'package_width_cm' => 20,
        'package_height_cm' => 20,
        'package_weight_kg' => 2,
    ]);

    $this->actingAs($fixtures['admin'], 'admin');

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\PickupPage::class)
        ->call('scanPickupQr', 'cargo-pickup:'.$order->pickup_token)
        ->assertHasNoErrors()
        ->assertSet('order.id', $order->id)
        ->assertSet('pickupToken', $order->pickup_token);
});

it('loads order from tajikistan pickup url via scanPickupQr', function () {
    $fixtures = makePickupPanelFixtures();
    $service = app(OrderPickupService::class);

    $order = $service->confirmPickup($fixtures['order'], $fixtures['admin'], [
        'package_length_cm' => 20,
        'package_width_cm' => 20,
        'package_height_cm' => 20,
        'package_weight_kg' => 2,
    ]);

    $this->actingAs($fixtures['admin'], 'admin');

    $url = route('tajikistan.pickup.show', ['token' => $order->pickup_token]);

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\PickupPage::class)
        ->call('scanPickupQr', $url)
        ->assertHasNoErrors()
        ->assertSet('order.id', $order->id)
        ->assertSet('pickupToken', $order->pickup_token);
});
