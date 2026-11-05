<?php

use App\Livewire\Admin\Order\ExceptionListPage as AdminExceptionListPage;
use App\Livewire\Admin\Order\OrderAuditLogListPage;
use App\Livewire\Warehouse\China\ExceptionListPage as ChinaExceptionListPage;
use App\Livewire\Warehouse\China\OrderDetailPage as ChinaOrderDetailPage;
use App\Livewire\Warehouse\China\ParcelScannerPage as ChinaParcelScannerPage;
use App\Livewire\Warehouse\Tajikistan\ParcelScannerPage as TajikistanParcelScannerPage;
use App\Livewire\Warehouse\Tajikistan\ProcessingPage as TajikistanProcessingPage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderException;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Services\Warehouse\OrderExceptionService;
use App\Services\Warehouse\ParcelScannerService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function makeOpsTestAdmin(string $role, ?int $warehouseId = null): Admin
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

function makeOpsTestWarehouse(string $code = 'WH-OPS-01'): Warehouse
{
    return Warehouse::create([
        'warehouse_name' => 'Dushanbe Ops '.$code,
        'warehouse_code' => $code,
        'contact_person' => 'Manager',
        'contact_number' => '+992900000100',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 10',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
}

function makeOpsTestOrder(User $user, Warehouse $warehouse, array $overrides = []): CustomerOrder
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

it('auto-generates a unique parcel barcode/qr code for every order and supports continuous scanning in china and tajikistan', function () {
    $chinaAdmin = makeOpsTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = makeOpsTestWarehouse();
    $tajikistanAdmin = makeOpsTestAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $user = User::factory()->create();

    $order1 = makeOpsTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-SCAN-001',
        'parcel_tracking_id' => 'TRK-SCAN-001',
    ]);
    $order2 = makeOpsTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-SCAN-002',
        'parcel_tracking_id' => 'TRK-SCAN-002',
    ]);

    // Every parcel has a unique auto-generated parcel_code & valid Code-128 SVG barcode
    expect($order1->parcel_code)->toStartWith('PCL-')
        ->and($order2->parcel_code)->toStartWith('PCL-')
        ->and($order1->parcel_code)->not->toBe($order2->parcel_code)
        ->and($order1->parcelQrPayload())->toBe('cargo-parcel:'.$order1->parcel_code)
        ->and(ParcelScannerService::renderCode128Svg($order1->parcel_code))->toContain('<svg');

    // Continuous scanning in China Warehouse using parcel_code and parcel_tracking_id without opening orders individually
    $this->actingAs($chinaAdmin, 'admin');

    $scannerComponent = Livewire::test(ChinaParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('scanInput', $order1->parcelQrPayload())
        ->call('scanFromInput')
        ->assertHasNoErrors()
        ->set('scanInput', 'TRK-SCAN-002')
        ->call('scanFromInput')
        ->assertHasNoErrors();

    expect($order1->fresh()->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($order2->fresh()->status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($scannerComponent->get('scanHistory'))->toHaveCount(2);

    // Continuous camera scan in Tajikistan Warehouse using elim_order_id
    $this->actingAs($tajikistanAdmin, 'admin');

    Livewire::test(TajikistanParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_SORTING_CENTER)
        ->call('scanFromCamera', 'ORD-SCAN-001')
        ->assertHasNoErrors();

    expect($order1->fresh()->status)->toBe(OrderStatus::CODE_SORTING_CENTER);
});

it('processes incoming parcels in tajikistan warehouse: scans, records weight and dimensions, auto-calculates shipping, and marks ready for pickup', function () {
    $warehouse = makeOpsTestWarehouse('WH-PROC-01');
    $tajikistanAdmin = makeOpsTestAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $user = User::factory()->create();

    $method = ShippingMethod::create([
        'name' => 'Standard Road Cargo',
        'code' => 'road_std',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 0.001,
        'max_weight' => 100.000,
        'rate_per_kg' => 25.00,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'mode' => ShippingRate::MODE_CM,
        'min_weight' => 0.001,
        'max_weight' => 100.000,
        'rate_per_kg' => 20.00,
        'is_active' => true,
    ]);

    $order = makeOpsTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-TJ-PROC-1',
        'status' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
        'shipping_method_id' => $method->id,
    ]);

    $this->actingAs($tajikistanAdmin, 'admin');

    $component = Livewire::test(TajikistanProcessingPage::class)
        ->set('scanInput', $order->parcel_code)
        ->call('scanIncomingParcel')
        ->assertHasNoErrors();

    // Order is automatically transitioned to arrived_in_tajikistan when scanned at processing station
    expect($order->fresh()->status)->toBe(OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN);

    // Record actual weight & dimensions in 'both' mode -> shipping cost is automatically calculated
    $component
        ->set('measurementMode', 'both')
        ->set('packageWeightG', '2000') // 2 kg * 25 = 50 TJS
        ->set('packageLengthCm', '30')
        ->set('packageWidthCm', '20')
        ->set('packageHeightCm', '10'); // volumetric = 6000/6000 = 1 kg * 20 = 20 TJS -> total 70 TJS

    $preview = $component->get('shippingPreview');
    expect($preview)->not->toBeNull()
        ->and((float) $preview['shipping_cost'])->toBe(70.0);

    // Complete processing -> order becomes Ready for Pickup
    $component->call('completeProcessing')
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::CODE_READY_FOR_PICKUP)
        ->and((float) $order->package_weight_kg)->toBe(2.0)
        ->and((float) $order->package_length_cm)->toBe(30.0)
        ->and((float) $order->pickup_shipping_fee_tjs)->toBe(70.0)
        ->and($order->pickup_token)->not->toBeNull()
        ->and($component->get('processedHistory'))->toHaveCount(1);
});

it('manages exceptions for unidentified shipments, missing barcodes, damaged parcels, and api errors without interrupting operations', function () {
    $chinaAdmin = makeOpsTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $superAdmin = makeOpsTestAdmin(Admin::ROLE_SUPER_ADMIN);
    $warehouse = makeOpsTestWarehouse('WH-EX-01');
    $user = User::factory()->create();

    $order = makeOpsTestOrder($user, $warehouse, ['elim_order_id' => 'ORD-EX-100']);

    // 1. Unidentified scan on China scanner can be flagged to Exception Management in 1 click
    $this->actingAs($chinaAdmin, 'admin');

    Livewire::test(ChinaParcelScannerPage::class)
        ->set('scanInput', 'UNKNOWN-BOX-999')
        ->call('scanFromInput')
        ->assertSet('lastUnidentifiedCode', 'UNKNOWN-BOX-999')
        ->call('flagUnidentifiedAsException', OrderException::TYPE_UNIDENTIFIED_SHIPMENT);

    $unidentifiedEx = OrderException::query()->where('scanned_code', 'UNKNOWN-BOX-999')->first();
    expect($unidentifiedEx)->not->toBeNull()
        ->and($unidentifiedEx->type)->toBe(OrderException::TYPE_UNIDENTIFIED_SHIPMENT)
        ->and($unidentifiedEx->status)->toBe(OrderException::STATUS_OPEN);

    // 2. Staff can report a damaged parcel or missing barcode in Exception Management
    Livewire::test(ChinaExceptionListPage::class)
        ->set('newType', OrderException::TYPE_DAMAGED_PARCEL)
        ->set('newOrderIdentifier', $order->parcel_code)
        ->set('newSummary', 'Outer box water damage')
        ->call('createException')
        ->assertHasNoErrors();

    // 3. Elim API error is automatically logged into Exception Management
    app(OrderExceptionService::class)->reportElimApiError(
        $order,
        'refreshOrder',
        'Elim API upstream timeout 504'
    );

    expect(OrderException::query()->where('type', OrderException::TYPE_API_ERROR)->exists())->toBeTrue();

    // 4. Super Admin or Warehouse Staff can link an unidentified exception to an order and mark it resolved
    $this->actingAs($superAdmin, 'admin');

    Livewire::test(AdminExceptionListPage::class)
        ->call('startReview', $unidentifiedEx->id)
        ->set('editStatus', OrderException::STATUS_RESOLVED)
        ->set('editLinkOrderIdentifier', $order->parcel_code)
        ->set('editResolutionNotes', 'Matched to customer order via invoice inside box')
        ->call('saveReview')
        ->assertHasNoErrors();

    $unidentifiedEx->refresh();
    expect($unidentifiedEx->status)->toBe(OrderException::STATUS_RESOLVED)
        ->and($unidentifiedEx->customer_order_id)->toBe($order->id)
        ->and($unidentifiedEx->resolved_by_admin_id)->toBe($superAdmin->id);
});

it('records every status change in the order history and audit log with date, time, employee id, and update source', function () {
    $chinaAdmin = makeOpsTestAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $superAdmin = makeOpsTestAdmin(Admin::ROLE_SUPER_ADMIN);
    $warehouse = makeOpsTestWarehouse('WH-AUDIT-01');
    $user = User::factory()->create();

    // 1. Initial creation records 'elim_api' source
    $order = makeOpsTestOrder($user, $warehouse, [
        'elim_order_id' => 'ORD-AUDIT-001',
        'status' => OrderStatus::CODE_PAID,
    ]);

    // 2. Barcode scan records 'barcode_scan' source + China employee ID
    $this->actingAs($chinaAdmin, 'admin');
    Livewire::test(ChinaParcelScannerPage::class)
        ->set('targetStatusCode', OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->set('scanInput', $order->parcel_code)
        ->call('scanFromInput')
        ->assertHasNoErrors();

    // 3. Manual update on OrderDetailPage records 'manual' source + China employee ID
    Livewire::test(ChinaOrderDetailPage::class, ['order' => $order->fresh()])
        ->set('statusCode', OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->call('updateStatus')
        ->assertHasNoErrors();

    // 4. Elim API webhook update records 'elim_api' source
    app(CustomerOrderLifecycleService::class)->applyWebhookOrderUpdate(
        $order->fresh(),
        ['id' => 'ORD-AUDIT-001', 'status' => 'delivered', 'payment_status' => 'paid'],
        ['id' => 'evt_1', 'event' => 'order.updated']
    );

    $histories = OrderStatusHistory::query()
        ->where('customer_order_id', $order->id)
        ->orderBy('id')
        ->get();

    expect($histories)->toHaveCount(4)
        ->and($histories[0]->to_status)->toBe(OrderStatus::CODE_PAID)
        ->and($histories[0]->source)->toBe(OrderStatusHistory::SOURCE_ELIM_API)
        ->and($histories[1]->from_status)->toBe(OrderStatus::CODE_PAID)
        ->and($histories[1]->to_status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($histories[1]->source)->toBe(OrderStatusHistory::SOURCE_BARCODE_SCAN)
        ->and($histories[1]->admin_id)->toBe($chinaAdmin->id)
        ->and($histories[2]->from_status)->toBe(OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE)
        ->and($histories[2]->to_status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->and($histories[2]->source)->toBe(OrderStatusHistory::SOURCE_MANUAL)
        ->and($histories[2]->admin_id)->toBe($chinaAdmin->id)
        ->and($histories[3]->from_status)->toBe(OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN)
        ->and($histories[3]->to_status)->toBe(OrderStatus::CODE_DELIVERED_TO_CUSTOMER)
        ->and($histories[3]->source)->toBe(OrderStatusHistory::SOURCE_ELIM_API);

    // Verify Admin Order Audit Log List Page renders all entries cleanly
    $this->actingAs($superAdmin, 'admin');
    Livewire::test(OrderAuditLogListPage::class)
        ->set('search', $order->parcel_code)
        ->assertSee($order->parcel_code)
        ->assertSee($chinaAdmin->name);
});
