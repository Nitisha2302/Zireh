<?php

use App\Livewire\Admin\ShippingMethod\ShippingMethodCreatePage;
use App\Livewire\Admin\ShippingMethod\ShippingMethodListPage;
use App\Livewire\Admin\ShippingRate\ShippingRateCreatePage;
use App\Livewire\Admin\ShippingRate\ShippingRateListPage;
use App\Models\Admin;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\Warehouse;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeShippingAdmin(): Admin
{
    return Admin::create([
        'name' => 'Shipping Admin',
        'username' => 'shippingadmin',
        'email' => 'shipping@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
        'role' => Admin::ROLE_SUPER_ADMIN,
    ]);
}

function makeShippingWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-SHIP',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000000',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Main Street 1',
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'status' => Warehouse::STATUS_ACTIVE,
    ], $overrides));
}

function validShippingMethodPayload(): array
{
    return [
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetricDivisor' => '6000',
        'minimumCharge' => '100',
        'isActive' => true,
    ];
}

it('shows shipping methods list to authenticated admins', function () {
    $admin = makeShippingAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.shipping-methods.index'))
        ->assertOk()
        ->assertSee('Shipping Methods');
});

it('creates a shipping method from admin panel', function () {
    $admin = makeShippingAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ShippingMethodCreatePage::class)
        ->set(validShippingMethodPayload())
        ->call('save')
        ->assertRedirect(route('admin.shipping-methods.index'));

    expect(ShippingMethod::query()->where('code', 'cargo')->exists())->toBeTrue();
});

it('requires warehouse when creating a shipping rate', function () {
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    $admin = makeShippingAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ShippingRateCreatePage::class)
        ->set('shippingMethodId', (string) $method->id)
        ->set('minWeight', '0')
        ->set('maxWeight', '5')
        ->set('ratePerKg', '25')
        ->call('save')
        ->assertHasErrors(['warehouseId']);
});

it('rejects overlapping shipping rate ranges for the same warehouse', function () {
    $warehouse = makeShippingWarehouse();
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'min_weight' => 0,
        'max_weight' => 5,
        'rate_per_kg' => 25,
        'is_active' => true,
    ]);

    $admin = makeShippingAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ShippingRateCreatePage::class)
        ->set('shippingMethodId', (string) $method->id)
        ->set('warehouseId', (string) $warehouse->id)
        ->set('minWeight', '4')
        ->set('maxWeight', '10')
        ->set('ratePerKg', '22')
        ->call('save')
        ->assertHasErrors(['minWeight']);
});

it('allows the same weight range for different warehouses', function () {
    $warehouseA = makeShippingWarehouse(['warehouse_code' => 'DUS-A']);
    $warehouseB = makeShippingWarehouse([
        'warehouse_name' => 'Khujand Hub',
        'warehouse_code' => 'KHJ-B',
        'city' => 'Khujand',
    ]);

    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouseA->id,
        'min_weight' => 0,
        'max_weight' => 5,
        'rate_per_kg' => 25,
        'is_active' => true,
    ]);

    $admin = makeShippingAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ShippingRateCreatePage::class)
        ->set('shippingMethodId', (string) $method->id)
        ->set('warehouseId', (string) $warehouseB->id)
        ->set('minWeight', '0')
        ->set('maxWeight', '5')
        ->set('ratePerKg', '30')
        ->call('save')
        ->assertRedirect(route('admin.shipping-rates.index'));

    expect(ShippingRate::query()->where('warehouse_id', $warehouseB->id)->exists())->toBeTrue();
});

it('soft deletes shipping method from list page', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 150,
        'is_active' => true,
    ]);

    $admin = makeShippingAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ShippingMethodListPage::class)
        ->set('deleteId', $method->id)
        ->call('onConfirmed');

    expect(ShippingMethod::withTrashed()->count())->toBe(1)
        ->and(ShippingMethod::count())->toBe(0);
});

it('calculates shipping cost using active rates and minimum charge', function () {
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 5,
        'rate_per_kg' => 25,
        'is_active' => true,
    ]);

    $result = app(ShippingRateService::class)->calculate('cargo', 2);

    expect($result['shipping_cost'])->toBe(100.0)
        ->and($result['chargeable_weight_kg'])->toBe(2.0)
        ->and($result['rate']['rate_per_kg'])->toBe(25.0);
});

it('prefers warehouse-specific rates when calculating shipping', function () {
    $warehouse = makeShippingWarehouse();
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 50,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => null,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 20,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 40,
        'is_active' => true,
    ]);

    $result = app(ShippingRateService::class)->calculate('cargo', 2, null, null, null, $warehouse->id);

    expect($result['rate']['rate_per_kg'])->toBe(40.0)
        ->and($result['rate']['warehouse_id'])->toBe($warehouse->id)
        ->and($result['shipping_cost'])->toBe(80.0);
});

it('lists only active shipping methods via api', function () {
    ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    ShippingMethod::create([
        'name' => 'Sea',
        'code' => 'sea',
        'volumetric_divisor' => 7000,
        'minimum_charge' => 80,
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/shipping/methods')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.methods');
});

it('calculates shipping via api endpoint', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 150,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 50,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'weight_kg' => 3,
    ])
        ->assertOk()
        ->assertJsonPath('data.shipping_cost', 150);
});

it('shows shipping rates list with warehouse column', function () {
    $warehouse = makeShippingWarehouse();
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 6000,
        'minimum_charge' => 100,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'warehouse_id' => $warehouse->id,
        'min_weight' => 0,
        'max_weight' => 5,
        'rate_per_kg' => 25,
        'is_active' => true,
    ]);

    $admin = makeShippingAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.shipping-rates.index'))
        ->assertOk()
        ->assertSee('Dushanbe Hub')
        ->assertSee('DUS-SHIP');
});
