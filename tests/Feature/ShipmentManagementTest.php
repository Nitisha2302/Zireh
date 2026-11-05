<?php

use App\Livewire\Warehouse\China\OrderDetailPage as ChinaOrderDetailPage;
use App\Livewire\Warehouse\China\ShipmentCreatePage;
use App\Livewire\Warehouse\China\ShipmentDetailPage as ChinaShipmentDetailPage;
use App\Livewire\Warehouse\Tajikistan\OrderDetailPage as TajikistanOrderDetailPage;
use App\Livewire\Warehouse\Tajikistan\ShipmentDetailPage as TajikistanShipmentDetailPage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Warehouse\ShipmentService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function createShipmentTestAdmin(string $role, ?int $warehouseId = null): Admin
{
    return Admin::create([
        'name' => ucfirst(str_replace('_', ' ', $role)),
        'username' => $role.'_'.uniqid(),
        'email' => $role.'_'.uniqid().'@example.com',
        'role' => $role,
        'warehouse_id' => $warehouseId,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
}

function createShipmentTestWarehouse(string $code = 'WH-DUS-01'): Warehouse
{
    return Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub '.$code,
        'warehouse_code' => $code,
        'contact_person' => 'Manager',
        'contact_number' => '+992900000001',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 1',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
}

function createShipmentTestOrder(User $user, Warehouse $warehouse, array $overrides = []): CustomerOrder
{
    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-'.random_int(10000, 99999),
        'status' => OrderStatus::CODE_PAID,
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
        'address_snapshot' => ['full_name' => 'Customer'],
    ], $overrides));
}

it('allows china warehouse to create a shipment with a unique shipment id and multiple unassigned orders', function () {
    $chinaAdmin = createShipmentTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = createShipmentTestWarehouse();
    $user = User::factory()->create();

    $order1 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-CN-001',
        'status' => OrderStatus::CODE_PAID,
    ]);
    $order2 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-CN-002',
        'status' => OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
    ]);
    $orderUnselected = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-CN-003',
        'status' => OrderStatus::CODE_PAID,
    ]);

    $this->actingAs($chinaAdmin, 'admin');

    Livewire::test(ShipmentCreatePage::class)
        ->set('shipmentCode', 'SHP-2026-0001')
        ->set('statusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('selectedOrderIds', [$order1->id, $order2->id])
        ->set('notes', 'First batch to Dushanbe')
        ->call('save')
        ->assertHasNoErrors();

    $shipment = Shipment::query()->where('shipment_code', 'SHP-2026-0001')->first();

    expect($shipment)->not->toBeNull()
        ->and($shipment->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($shipment->isReceivedInTajikistan())->toBeFalse()
        ->and($order1->fresh()->shipment_id)->toBe($shipment->id)
        ->and($order1->fresh()->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($order2->fresh()->shipment_id)->toBe($shipment->id)
        ->and($order2->fresh()->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($orderUnselected->fresh()->shipment_id)->toBeNull();

    // Unassigned orders dropdown only includes orders not yet assigned to any shipment
    $unassignedIds = app(ShipmentService::class)->listUnassignedOrders()->pluck('id')->all();
    expect($unassignedIds)->toContain($orderUnselected->id)
        ->not->toContain($order1->id)
        ->not->toContain($order2->id);
});

it('rejects duplicate shipment ids and already assigned orders when creating a shipment', function () {
    $chinaAdmin = createShipmentTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = createShipmentTestWarehouse();
    $user = User::factory()->create();

    $order1 = createShipmentTestOrder($user, $warehouse, ['elim_order_id' => 'ORD-DUP-001']);
    $order2 = createShipmentTestOrder($user, $warehouse, ['elim_order_id' => 'ORD-DUP-002']);

    $this->actingAs($chinaAdmin, 'admin');

    Livewire::test(ShipmentCreatePage::class)
        ->set('shipmentCode', 'SHP-UNIQUE-01')
        ->set('statusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('selectedOrderIds', [$order1->id])
        ->call('save')
        ->assertHasNoErrors();

    // Duplicate shipment code must fail validation
    Livewire::test(ShipmentCreatePage::class)
        ->set('shipmentCode', 'SHP-UNIQUE-01')
        ->set('statusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('selectedOrderIds', [$order2->id])
        ->call('save')
        ->assertHasErrors(['shipment_code']);
});

it('automatically updates all associated order statuses when the shipment status is updated in china warehouse', function () {
    $chinaAdmin = createShipmentTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = createShipmentTestWarehouse();
    $user = User::factory()->create();

    $order1 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-CASCADE-1',
        'status' => OrderStatus::CODE_PAID,
    ]);
    $order2 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-CASCADE-2',
        'status' => OrderStatus::CODE_PAID,
    ]);

    $shipment = app(ShipmentService::class)->createShipment($chinaAdmin, [
        'shipment_code' => 'SHP-CASCADE-100',
        'status' => OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
        'order_ids' => [$order1->id, $order2->id],
    ]);

    $this->actingAs($chinaAdmin, 'admin');

    Livewire::test(ChinaShipmentDetailPage::class, ['shipment' => $shipment])
        ->set('statusCode', OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($shipment->fresh()->status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->and($order1->fresh()->status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->and($order2->fresh()->status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN);
});

it('blocks individual order status updates before the shipment is received in tajikistan', function () {
    $chinaAdmin = createShipmentTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = createShipmentTestWarehouse();
    $tajikistanAdmin = createShipmentTestAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $user = User::factory()->create();

    $order = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-LOCKED-1',
        'status' => OrderStatus::CODE_PAID,
    ]);

    app(ShipmentService::class)->createShipment($chinaAdmin, [
        'shipment_code' => 'SHP-LOCKED-100',
        'status' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
        'order_ids' => [$order->id],
    ]);

    $order->refresh();
    expect($order->isManagedByShipment())->toBeTrue();

    // China Warehouse cannot update individual order status while managed by shipment
    $this->actingAs($chinaAdmin, 'admin');
    Livewire::test(ChinaOrderDetailPage::class, ['order' => $order])
        ->set('statusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->call('updateStatus')
        ->assertHasErrors(['statusCode']);

    // Tajikistan Warehouse cannot update individual order status before shipment is received in Tajikistan
    $this->actingAs($tajikistanAdmin, 'admin');
    Livewire::test(TajikistanOrderDetailPage::class, ['order' => $order])
        ->set('statusCode', OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
        ->call('updateStatus')
        ->assertHasErrors(['statusCode']);

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN);
});

it('locks shipment status updates once received in tajikistan and enables independent order status updates without affecting shipment status', function () {
    $chinaAdmin = createShipmentTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = createShipmentTestWarehouse();
    $tajikistanAdmin = createShipmentTestAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $user = User::factory()->create();

    $order1 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-TJ-101',
        'status' => OrderStatus::CODE_PAID,
    ]);
    $order2 = createShipmentTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-TJ-102',
        'status' => OrderStatus::CODE_PAID,
    ]);

    $shipment = app(ShipmentService::class)->createShipment($chinaAdmin, [
        'shipment_code' => 'SHP-TJ-2026-01',
        'status' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
        'order_ids' => [$order1->id, $order2->id],
    ]);

    // Tajikistan Warehouse marks shipment as received
    $this->actingAs($tajikistanAdmin, 'admin');

    Livewire::test(TajikistanShipmentDetailPage::class, ['shipment' => $shipment])
        ->call('markReceived')
        ->assertHasNoErrors();

    $shipment->refresh();
    $order1->refresh();
    $order2->refresh();

    expect($shipment->isReceivedInTajikistan())->toBeTrue()
        ->and($shipment->isStatusUpdateLocked())->toBeTrue()
        ->and($shipment->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
        ->and($order1->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
        ->and($order2->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
        ->and($order1->isManagedByShipment())->toBeFalse()
        ->and($order2->isManagedByShipment())->toBeFalse();

    // Shipment status update option is now disabled/locked even for China Warehouse
    $this->actingAs($chinaAdmin, 'admin');
    Livewire::test(ChinaShipmentDetailPage::class, ['shipment' => $shipment])
        ->set('statusCode', OrderStatus::CODE_SORTING_CENTER)
        ->call('updateStatus')
        ->assertHasErrors(['statusCode']);

    expect($shipment->fresh()->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN);

    // Tajikistan Warehouse staff can now update each order individually without affecting shipment status or other orders
    $this->actingAs($tajikistanAdmin, 'admin');
    Livewire::test(TajikistanOrderDetailPage::class, ['order' => $order1])
        ->set('statusCode', OrderStatus::CODE_SORTING_CENTER)
        ->call('updateStatus')
        ->assertHasNoErrors();

    // Flow-only statuses (Delivered / Ready for Pickup) cannot be set manually by warehouse staff.
    Livewire::test(TajikistanOrderDetailPage::class, ['order' => $order2])
        ->set('statusCode', OrderStatus::CODE_DELIVERED_TO_CUSTOMER)
        ->call('updateStatus')
        ->assertHasErrors(['statusCode']);

    expect($order1->fresh()->status)->toBe(OrderStatus::CODE_SORTING_CENTER)
        ->and($order2->fresh()->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN)
        ->and($shipment->fresh()->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN);
});
