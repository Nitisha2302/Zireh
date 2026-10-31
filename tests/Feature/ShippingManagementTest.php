<?php

use App\Livewire\Admin\ShippingMethod\ShippingMethodCreatePage;
use App\Livewire\Admin\ShippingMethod\ShippingMethodListPage;
use App\Livewire\Admin\ShippingRate\ShippingRateCreatePage;
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
        ->set('mode', 'weight')
        ->set('minWeight', '4000')
        ->set('maxWeight', '10000')
        ->set('ratePerKg', '22')
        ->call('save')
        ->assertHasErrors(['minWeight']);
});

it('allows overlapping ranges across weight and cm ladders', function () {
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
        'mode' => ShippingRate::MODE_WEIGHT,
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
        ->set('mode', 'cm')
        ->set('minWeight', '0')
        ->set('maxWeight', '5')
        ->set('ratePerKg', '22')
        ->call('save')
        ->assertRedirect(route('admin.shipping-rates.index'));

    expect(ShippingRate::query()->where('mode', ShippingRate::MODE_CM)->exists())->toBeTrue();
});

it('stores weight ladder bounds from grams', function () {
    $warehouse = makeShippingWarehouse();
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
        ->set('warehouseId', (string) $warehouse->id)
        ->set('mode', 'weight')
        ->set('minWeight', '1500')
        ->set('maxWeight', '5000')
        ->set('ratePerKg', '25')
        ->call('save')
        ->assertRedirect(route('admin.shipping-rates.index'));

    $rate = ShippingRate::query()->first();

    expect($rate->mode)->toBe(ShippingRate::MODE_WEIGHT)
        ->and((float) $rate->min_weight)->toBe(1.5)
        ->and((float) $rate->max_weight)->toBe(5.0)
        ->and($rate->minWeightGrams())->toBe(1500.0)
        ->and($rate->weightRangeLabel())->toContain('1,500 g');
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
        ->assertJsonPath('data.shipping_cost', 150)
        ->assertJsonPath('data.mode', 'auto');
});

it('calculates shipping by weight grams via api', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'weight',
        'weight_g' => 1500,
        'length_cm' => 80,
        'width_cm' => 80,
        'height_cm' => 80,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'weight')
        ->assertJsonPath('data.actual_weight_kg', 1.5)
        ->assertJsonPath('data.actual_weight_g', 1500)
        ->assertJsonPath('data.volumetric_weight_kg', null)
        ->assertJsonPath('data.shipping_cost', 15);
});

it('uses separate weight and cm rate ladders via api', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_CM,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 50,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'weight',
        'weight_g' => 1500,
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'weight')
        ->assertJsonPath('data.rate.mode', 'weight')
        ->assertJsonPath('data.weight_cost_tjs', 15)
        ->assertJsonPath('data.volume_cost_tjs', 0)
        ->assertJsonPath('data.package.weight_g', 1500)
        ->assertJsonPath('data.package.length_cm', null)
        ->assertJsonPath('data.shipping_cost', 15);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'cm',
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'cm')
        ->assertJsonPath('data.rate.mode', 'cm')
        ->assertJsonPath('data.volumetric_weight_kg', 12)
        ->assertJsonPath('data.weight_cost_tjs', 0)
        ->assertJsonPath('data.volume_cost_tjs', 600)
        ->assertJsonPath('data.package.length_cm', 50)
        ->assertJsonPath('data.package.volume_m3', 0.06)
        ->assertJsonPath('data.shipping_cost', 600);

    $this->postJson('/api/v1/public/shipping/calculate', [
        'method' => 'air',
        'mode' => 'BOTH',
        'weight_g' => 1500,
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'both')
        ->assertJsonPath('data.actual_weight_kg', 1.5)
        ->assertJsonPath('data.actual_weight_g', 1500)
        ->assertJsonPath('data.volumetric_weight_kg', 12)
        ->assertJsonPath('data.package.weight_kg', 1.5)
        ->assertJsonPath('data.package.weight_g', 1500)
        ->assertJsonPath('data.package.length_cm', 50)
        ->assertJsonPath('data.package.width_cm', 40)
        ->assertJsonPath('data.package.height_cm', 30)
        ->assertJsonPath('data.package.volume_m3', 0.06)
        ->assertJsonPath('data.package.volumetric_weight_kg', 12)
        ->assertJsonPath('data.weight_cost_tjs', 15)
        ->assertJsonPath('data.volume_cost_tjs', 600)
        ->assertJsonPath('data.shipping_cost', 615);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'both',
        'weight_g' => 1500,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['length_cm', 'width_cm', 'height_cm']);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'both',
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['weight_g', 'weight_kg']);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'both',
        'weight_kg' => 0,
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['weight_kg']);
});

it('falls back to the other ladder when matching rates are missing', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'cm',
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'cm')
        ->assertJsonPath('data.rate.mode', 'weight')
        ->assertJsonPath('data.shipping_cost', 120);
});

it('lists rate mode and gram bounds on shipping methods api', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 1.5,
        'max_weight' => 5,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/shipping/methods')
        ->assertOk()
        ->assertJsonPath('data.methods.0.rates.0.mode', 'weight')
        ->assertJsonPath('data.methods.0.rates.0.min_weight_g', 1500)
        ->assertJsonPath('data.methods.0.rates.0.max_weight_g', 5000);
});

it('calculates shipping by centimeters via api', function () {
    $method = ShippingMethod::create([
        'name' => 'Air',
        'code' => 'air',
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

    $this->postJson('/api/v1/shipping/calculate', [
        'method' => 'air',
        'mode' => 'cm',
        'length_cm' => 50,
        'width_cm' => 40,
        'height_cm' => 30,
    ])
        ->assertOk()
        ->assertJsonPath('data.mode', 'cm')
        ->assertJsonPath('data.actual_weight_kg', null)
        ->assertJsonPath('data.volumetric_weight_kg', 12)
        ->assertJsonPath('data.shipping_cost', 120);
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
        ->assertSee('DUS-SHIP')
        ->assertSee('Weight (g)');
});
