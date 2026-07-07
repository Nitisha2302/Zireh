<?php

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

function makeWarehousePanelAdmin(string $role, ?int $warehouseId = null): Admin
{
    return Admin::create([
        'name' => ucfirst(str_replace('_', ' ', $role)),
        'username' => $role.'_'.uniqid(),
        'email' => $role.'@example.com',
        'role' => $role,
        'warehouse_id' => $warehouseId,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
}

function makeWarehouseOrder(User $user, Warehouse $warehouse, array $overrides = []): CustomerOrder
{
    return CustomerOrder::create(array_merge([
        'user_id' => $user->id,
        'warehouse_id' => $warehouse->id,
        'platform' => 'taobao',
        'elim_order_id' => 'ORD'.random_int(1000, 9999),
        'status' => 'paid',
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

it('rejects warehouse staff on admin login and authenticates via china login', function () {
    $admin = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);

    Livewire::test(\App\Livewire\Authenticate\LoginPage::class)
        ->set('login', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('login');

    Livewire::test(\App\Livewire\Warehouse\China\LoginPage::class)
        ->set('login', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('china.orders.index'));
});

it('rejects super admin on china and tajikistan warehouse logins', function () {
    $admin = makeWarehousePanelAdmin(Admin::ROLE_SUPER_ADMIN);

    Livewire::test(\App\Livewire\Warehouse\China\LoginPage::class)
        ->set('login', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('login');

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\LoginPage::class)
        ->set('login', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('login');
});

it('rejects china staff on tajikistan login and tajikistan staff on china login', function () {
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-LOGIN',
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

    $china = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $tajikistan = makeWarehousePanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\LoginPage::class)
        ->set('login', $china->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('login');

    Livewire::test(\App\Livewire\Warehouse\China\LoginPage::class)
        ->set('login', $tajikistan->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('login');
});

it('shows china warehouse orders and blocks cancelled orders from detail', function () {
    $admin = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-01',
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
    $user = User::factory()->create();
    $visibleOrder = makeWarehouseOrder($user, $warehouse, ['elim_order_id' => 'ORD-VISIBLE']);
    $hiddenOrder = makeWarehouseOrder($user, $warehouse, ['status' => 'cancelled', 'elim_order_id' => 'ORD-HIDDEN']);

    $this->actingAs($admin, 'admin')
        ->get(route('china.orders.index'))
        ->assertOk()
        ->assertSee('ORD-VISIBLE')
        ->assertDontSee('ORD-HIDDEN');

    $this->actingAs($admin, 'admin')
        ->get(route('china.orders.show', $visibleOrder))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->get(route('china.orders.show', $hiddenOrder))
        ->assertNotFound();
});

it('allows china warehouse staff to update parcel tracking', function () {
    $admin = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-02',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000002',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 2',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
    $order = makeWarehouseOrder(User::factory()->create(), $warehouse);

    $this->actingAs($admin, 'admin');

    Livewire::test(\App\Livewire\Warehouse\China\OrderDetailPage::class, ['order' => $order])
        ->set('parcelTrackingId', 'TRACK-12345')
        ->call('updateParcelTracking')
        ->assertHasNoErrors();

    expect($order->fresh()->parcel_tracking_id)->toBe('TRACK-12345');
});

it('restricts tajikistan warehouse staff to their assigned warehouse orders', function () {
    $warehouseA = Warehouse::create([
        'warehouse_name' => 'Warehouse A',
        'warehouse_code' => 'WH-A',
        'contact_person' => 'A',
        'contact_number' => '+992900000010',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'A Street',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);
    $warehouseB = Warehouse::create([
        'warehouse_name' => 'Warehouse B',
        'warehouse_code' => 'WH-B',
        'contact_person' => 'B',
        'contact_number' => '+992900000011',
        'country' => 'Tajikistan',
        'state' => 'Khujand',
        'city' => 'Khujand',
        'address' => 'B Street',
        'latitude' => 40.28,
        'longitude' => 69.62,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $admin = makeWarehousePanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouseA->id);
    $user = User::factory()->create();
    $ownOrder = makeWarehouseOrder($user, $warehouseA, ['elim_order_id' => 'ORD-OWN']);
    $otherOrder = makeWarehouseOrder($user, $warehouseB, ['elim_order_id' => 'ORD-OTHER']);

    $this->actingAs($admin, 'admin')
        ->get(route('tajikistan.orders.index'))
        ->assertOk()
        ->assertSee('ORD-OWN')
        ->assertDontSee('ORD-OTHER');

    $this->actingAs($admin, 'admin')
        ->get(route('tajikistan.orders.show', $ownOrder))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->get(route('tajikistan.orders.show', $otherOrder))
        ->assertForbidden();
});

it('blocks warehouse staff from accessing the other panel routes', function () {
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Warehouse A',
        'warehouse_code' => 'WH-CROSS',
        'contact_person' => 'A',
        'contact_number' => '+992900000012',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'A Street',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $china = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $tajikistan = makeWarehousePanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);

    $this->actingAs($china, 'admin')
        ->get(route('tajikistan.orders.index'))
        ->assertForbidden();

    $this->actingAs($tajikistan, 'admin')
        ->get(route('china.orders.index'))
        ->assertForbidden();
});

it('blocks warehouse staff and super admin from admin dashboard', function () {
    $china = makeWarehousePanelAdmin(Admin::ROLE_CHINA_WAREHOUSE);
    $super = makeWarehousePanelAdmin(Admin::ROLE_SUPER_ADMIN);

    $this->actingAs($china, 'admin')
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('china.orders.index'));

    $this->actingAs($super, 'admin')
        ->get(route('china.orders.index'))
        ->assertForbidden();

    $this->actingAs($super, 'admin')
        ->get(route('tajikistan.orders.index'))
        ->assertForbidden();
});

it('redirects guests on panel routes to the correct login page', function () {
    $this->get(route('china.orders.index'))
        ->assertRedirect(route('china.login'));

    $this->get(route('tajikistan.orders.index'))
        ->assertRedirect(route('tajikistan.login'));
});

it('authenticates tajikistan warehouse staff via tajikistan login', function () {
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Warehouse TJ',
        'warehouse_code' => 'WH-TJ',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000013',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $admin = makeWarehousePanelAdmin(Admin::ROLE_TAJIKISTAN_WAREHOUSE, $warehouse->id);

    Livewire::test(\App\Livewire\Warehouse\Tajikistan\LoginPage::class)
        ->set('login', $admin->email)
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('tajikistan.orders.index'));
});
