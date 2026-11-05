<?php

use App\Livewire\Warehouse\China\ParcelScannerPage as ChinaParcelScannerPage;
use App\Livewire\Warehouse\Tajikistan\ParcelScannerPage as TajikistanParcelScannerPage;
use App\Livewire\Warehouse\Tajikistan\ProcessingPage as TajikistanProcessingPage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderException;
use App\Models\OrderStatus;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Services\Warehouse\OrderExceptionService;
use App\Services\Warehouse\ShipmentService;
use App\Services\Warehouse\WarehousePanelService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function crAdmin(string $role, ?int $warehouseId = null): Admin
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

function crWarehouse(string $code): Warehouse
{
    return Warehouse::create([
        'warehouse_name' => 'CR '.$code,
        'warehouse_code' => $code,
        'contact_person' => 'Manager',
        'contact_number' => '+992900000200',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 20',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
}

function crOrder(Warehouse $warehouse, array $overrides = []): CustomerOrder
{
    $user = User::factory()->create();

    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-CR-'.random_int(10000, 99999),
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

function crShippingMethod(string $code, Warehouse $warehouse, float $ratePerKg): ShippingMethod
{
    $method = ShippingMethod::create([
        'name' => 'Method '.$code,
        'code' => $code,
        'volumetric_divisor' => 6000,
        'minimum_charge' => 0,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 0.001,
        'max_weight' => 100,
        'rate_per_kg' => $ratePerKg,
        'is_active' => true,
    ]);

    return $method;
}

it('generates a fresh parcel code and pickup token when an order is replicated', function () {
    $order = crOrder(crWarehouse('CR-REP'), ['pickup_token' => 'tok-123']);

    $copy = $order->replicate();
    $copy->elim_order_id = 'ORD-CR-COPY';
    $copy->save();

    expect($copy->parcel_code)->toStartWith('PCL-')
        ->and($copy->parcel_code)->not->toBe($order->parcel_code)
        ->and($copy->pickup_token)->toBeNull();
});

it('never matches an unknown numeric barcode to an internal order id, but accepts an explicit #id and lowercase parcel codes', function () {
    $china = crAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $order = crOrder(crWarehouse('CR-NUM'));

    $this->actingAs($china, 'admin');

    Livewire::test(ChinaParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('scanInput', (string) $order->id)
        ->call('scanFromInput')
        ->assertHasErrors('scanInput');

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_PAID);

    Livewire::test(ChinaParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('scanInput', strtolower($order->parcel_code))
        ->call('scanFromInput')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE);

    Livewire::test(ChinaParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->set('scanInput', '#'.$order->id)
        ->call('scanFromInput')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN);
});

it('prevents cancelled orders from being revived by the scanner or manual warehouse updates', function () {
    $warehouse = crWarehouse('CR-CXL');
    $tajik = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $order = crOrder($warehouse, [
        'status' => OrderStatus::CODE_CANCELLED,
        'payment_status' => 'refunded',
        'cancelled_at' => now(),
    ]);

    $this->actingAs($tajik, 'admin');

    Livewire::test(TajikistanParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_SORTING_CENTER)
        ->set('scanInput', $order->parcel_code)
        ->call('scanFromInput')
        ->assertHasErrors('scanInput');

    expect(fn () => app(WarehousePanelService::class)->updateOrderStatus($order->fresh(), OrderStatus::CODE_SORTING_CENTER))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_CANCELLED);
});

it('rejects flow-only statuses (ready for pickup / delivered) from scanner, manual warehouse update, and shipments', function () {
    $warehouse = crWarehouse('CR-FLOW');
    $china = crAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $tajik = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $order = crOrder($warehouse, ['status' => OrderStatus::CODE_SORTING_CENTER]);

    $this->actingAs($tajik, 'admin');

    Livewire::test(TajikistanParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_READY_FOR_PICKUP)
        ->set('scanInput', $order->parcel_code)
        ->call('scanFromInput')
        ->assertHasErrors();

    expect(fn () => app(WarehousePanelService::class)->updateOrderStatus($order->fresh(), OrderStatus::CODE_DELIVERED_TO_CUSTOMER))
        ->toThrow(ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_SORTING_CENTER)
        ->and($order->fresh()->pickup_token)->toBeNull();

    $other = crOrder($warehouse);
    expect(fn () => app(ShipmentService::class)->createShipment($china, [
        'shipment_code' => 'SHP-CR-FLOW',
        'status' => OrderStatus::CODE_READY_FOR_PICKUP,
        'order_ids' => [$other->id],
    ]))->toThrow(ValidationException::class);
});

it('scopes exception review to the staff member warehouse (no ID tampering)', function () {
    $warehouseA = crWarehouse('CR-EXA');
    $warehouseB = crWarehouse('CR-EXB');
    $staffA = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouseA->id);
    $staffB = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouseB->id);

    $foreign = app(OrderExceptionService::class)->reportException([
        'type' => OrderException::TYPE_DAMAGED_PARCEL,
        'origin_panel' => 'tajikistan',
        'warehouse_id' => $warehouseB->id,
        'summary' => 'Box crushed',
    ], $staffB);

    expect(fn () => app(OrderExceptionService::class)->findForPanel($staffA, 'tajikistan', $foreign->id))
        ->toThrow(NotFoundHttpException::class);

    expect(app(OrderExceptionService::class)->findForPanel($staffB, 'tajikistan', $foreign->id)->id)
        ->toBe($foreign->id);
});

it('reuses an open api_error exception instead of creating duplicates', function () {
    $order = crOrder(crWarehouse('CR-API'));
    $service = app(OrderExceptionService::class);

    $first = $service->reportElimApiError($order, 'detail', 'timeout 1');
    $second = $service->reportElimApiError($order, 'detail', 'timeout 2');
    $service->reportElimApiError($order, 'cancel', 'cancel failed');

    expect($second->id)->toBe($first->id)
        ->and($second->details)->toBe('timeout 2')
        ->and($second->error_payload['occurrences'])->toBe(2)
        ->and(OrderException::query()->where('type', OrderException::TYPE_API_ERROR)->count())->toBe(2);
});

it('maps elim statuses to the correct china-side stages and refunds when elim closes an order', function () {
    expect(OrderStatus::mapFromElimStatus('completed'))->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and(OrderStatus::mapFromElimStatus('delivered'))->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and(OrderStatus::mapFromElimStatus('shipped'))->toBeNull()
        ->and(OrderStatus::mapFromElimStatus('in_transit'))->toBeNull()
        ->and(OrderStatus::mapFromElimStatus('closed'))->toBe(OrderStatus::CODE_CANCELLED);

    $order = crOrder(crWarehouse('CR-ELIM'));

    $updated = app(CustomerOrderLifecycleService::class)->applyWebhookOrderUpdate(
        $order,
        ['id' => $order->elim_order_id, 'status' => 'closed'],
        ['id' => 'evt_cr', 'event' => 'order.updated']
    );

    expect($updated->status)->toBe(OrderStatus::CODE_CANCELLED)
        ->and($updated->payment_status)->toBe(CustomerOrder::PAYMENT_STATUS_REFUNDED)
        ->and($updated->cancellation_refund_transaction_id)->not->toBeNull();
});

it('rejects processing-station scans of orders that have not left china yet', function () {
    $warehouse = crWarehouse('CR-PRC');
    $tajik = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $order = crOrder($warehouse, ['status' => OrderStatus::CODE_PAID]);

    $this->actingAs($tajik, 'admin');

    Livewire::test(TajikistanProcessingPage::class)
        ->set('scanInput', $order->parcel_code)
        ->call('scanIncomingParcel')
        ->assertHasErrors('order')
        ->assertSet('order', null);

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_PAID);
});

it('does not persist the shipping method while previewing on the processing station', function () {
    $warehouse = crWarehouse('CR-PRV');
    $tajik = crAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $methodA = crShippingMethod('cr_a', $warehouse, 10);
    $methodB = crShippingMethod('cr_b', $warehouse, 30);

    $order = crOrder($warehouse, [
        'status' => OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
        'shipping_method_id' => $methodA->id,
    ]);

    $this->actingAs($tajik, 'admin');

    $component = Livewire::test(TajikistanProcessingPage::class)
        ->set('scanInput', $order->parcel_code)
        ->call('scanIncomingParcel')
        ->assertHasNoErrors()
        ->set('shippingMethodId', $methodB->id)
        ->set('packageWeightG', '2000');

    expect((float) $component->get('shippingPreview')['shipping_cost'])->toBe(60.0)
        ->and($order->fresh()->shipping_method_id)->toBe($methodA->id);

    $component->call('completeProcessing')->assertHasNoErrors();

    expect($order->fresh()->shipping_method_id)->toBe($methodB->id)
        ->and($order->fresh()->status)->toBe(OrderStatus::CODE_READY_FOR_PICKUP);
});

it('detaches a cancelled order from its shipment and records the customer as actor', function () {
    $china = crAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = crWarehouse('CR-SHC');
    $order = crOrder($warehouse, ['is_demo_order' => true]);
    $sibling = crOrder($warehouse, ['is_demo_order' => true]);

    app(ShipmentService::class)->createShipment($china, [
        'shipment_code' => 'SHP-CR-CXL',
        'status' => OrderStatus::CODE_PAID,
        'order_ids' => [$order->id, $sibling->id],
    ]);

    $cancelled = app(CustomerOrderLifecycleService::class)->cancel($order->user, $order->fresh());

    expect($cancelled->status)->toBe(OrderStatus::CODE_CANCELLED)
        ->and($cancelled->fresh()->shipment_id)->toBeNull()
        ->and($sibling->fresh()->shipment_id)->not->toBeNull();

    $history = $cancelled->statusHistories()->first();
    expect($history->to_status)->toBe(OrderStatus::CODE_CANCELLED)
        ->and($history->notes)->toContain('by customer #'.$order->user_id)
        ->and($history->metadata['cancelled_by'])->toBe('customer');
});

it('rejects dc next callbacks that have no signature', function () {
    $this->postJson('/api/v1/webhooks/dc-next/invoice', [
        'order_id' => 'DCN-UNSIGNED-1',
        'payid' => 'PAY-X',
    ])->assertStatus(401);
});
