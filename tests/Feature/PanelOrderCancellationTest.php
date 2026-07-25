<?php

use App\Livewire\Admin\Order\OrderDetailPage as AdminOrderDetailPage;
use App\Livewire\Warehouse\China\OrderDetailPage as ChinaOrderDetailPage;
use App\Livewire\Warehouse\Tajikistan\OrderDetailPage as TajikistanOrderDetailPage;
use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Services\Order\OrderStatusService;
use App\Services\Wallet\WalletService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
    config([
        'services.elim.base_url' => 'https://openapi.elim.asia',
        'services.elim.demo_mode' => false,
    ]);
    Cache::forget(\App\Support\Elim\ElimApiConfig::CACHE_KEY);
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);
});

function makeCancelPanelAdmin(string $role, ?int $warehouseId = null): Admin
{
    return Admin::create([
        'name' => ucfirst(str_replace('_', ' ', $role)),
        'username' => $role.'_'.uniqid(),
        'email' => $role.uniqid().'@example.com',
        'role' => $role,
        'warehouse_id' => $warehouseId,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
}

function makeCancelPanelWarehouse(string $code = 'DUS-CANCEL'): Warehouse
{
    return Warehouse::create([
        'warehouse_name' => 'Cancel Hub',
        'warehouse_code' => $code,
        'contact_person' => 'Manager',
        'contact_number' => '+992900000100',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Cancel Street',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
}

function makePaidCancelOrder(User $user, ?Warehouse $warehouse = null, array $overrides = []): CustomerOrder
{
    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse?->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD-CANCEL-'.random_int(1000, 9999),
        'status' => OrderStatus::CODE_PAID,
        'payment_status' => CustomerOrder::PAYMENT_STATUS_PAID,
        'payment_method' => CustomerOrder::PAYMENT_METHOD_ONLINE,
        'goods_subtotal_cny' => 100,
        'shipping_fee_cny' => 10,
        'commission_amount' => 5,
        'commission_percentage' => 5,
        'customer_total_cny' => 115,
        'customer_total_tjs' => 150,
        'final_amount_tjs' => 150,
        'receiver_address' => ['name' => 'China WH'],
        'warehouse_snapshot' => $warehouse ? ['warehouse_name' => $warehouse->warehouse_name] : null,
        'address_snapshot' => ['full_name' => 'Customer'],
    ], $overrides));
}

it('lets admin cancel a paid order and credit the customer wallet', function () {
    $admin = makeCancelPanelAdmin(Admin::ROLE_SUPER_ADMIN);
    $user = User::factory()->create();
    $order = makePaidCancelOrder($user);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/*/cancel' => Http::response(['success' => true], 200),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(AdminOrderDetailPage::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::CODE_CANCELLED)
        ->and($order->payment_status)->toBe(CustomerOrder::PAYMENT_STATUS_REFUNDED)
        ->and($order->cancelled_by_admin_id)->toBe($admin->id)
        ->and((float) app(WalletService::class)->getBalance($user))->toBe(150.0)
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_ORDER_REFUND)->count())->toBe(1);
});

it('lets china warehouse staff cancel and redirects to order list', function () {
    $admin = makeCancelPanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = makeCancelPanelWarehouse('DUS-CN');
    $user = User::factory()->create();
    $order = makePaidCancelOrder($user, $warehouse, [
        'payment_method' => CustomerOrder::PAYMENT_METHOD_WALLET,
        'final_amount_tjs' => 90,
        'customer_total_tjs' => 90,
    ]);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/*/cancel' => Http::response(['success' => true], 200),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(ChinaOrderDetailPage::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertRedirect(route('china.orders.index'));

    expect((float) app(WalletService::class)->getBalance($user))->toBe(90.0)
        ->and($order->fresh()->status)->toBe(OrderStatus::CODE_CANCELLED);
});

it('lets assigned tajikistan warehouse staff cancel and refund', function () {
    $warehouse = makeCancelPanelWarehouse('DUS-TJ');
    $admin = makeCancelPanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);
    $user = User::factory()->create();
    $order = makePaidCancelOrder($user, $warehouse, ['final_amount_tjs' => 70, 'customer_total_tjs' => 70]);

    Http::fake([
        'https://openapi.elim.asia/v1/orders/*/cancel' => Http::response(['success' => true], 200),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(TajikistanOrderDetailPage::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertHasNoErrors();

    expect($order->fresh()->cancelled_by_admin_id)->toBe($admin->id)
        ->and((float) app(WalletService::class)->getBalance($user))->toBe(70.0);
});

it('blocks tajikistan staff from cancelling another warehouse order', function () {
    $warehouseA = makeCancelPanelWarehouse('DUS-A');
    $warehouseB = makeCancelPanelWarehouse('DUS-B');
    $admin = makeCancelPanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouseA->id);
    $order = makePaidCancelOrder(User::factory()->create(), $warehouseB);

    $this->actingAs($admin, 'admin')
        ->get(route('tajikistan.orders.show', $order))
        ->assertForbidden();
});

it('rejects direct status updates to cancelled', function () {
    $order = makePaidCancelOrder(User::factory()->create());

    expect(fn () => app(OrderStatusService::class)->updateOrderStatus($order, OrderStatus::CODE_CANCELLED))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($order->fresh()->status)->toBe(OrderStatus::CODE_PAID);
});
