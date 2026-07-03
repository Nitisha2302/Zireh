<?php

use App\Livewire\Admin\Warehouse\WarehouseCreatePage;
use App\Livewire\Admin\Warehouse\WarehouseEditPage;
use App\Livewire\Admin\Warehouse\WarehouseListPage;
use App\Models\Admin;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeWarehouseAdmin(): Admin
{
    return Admin::create([
        'name' => 'Warehouse Admin',
        'username' => 'warehouseadmin',
        'email' => 'warehouse@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

function validWarehousePayload(): array
{
    return [
        'warehouse_name' => 'Dushanbe Central Warehouse',
        'warehouse_code' => 'DUS-TJ-01',
        'contact_person' => 'Rustam Karimov',
        'contact_number' => '+992901234567',
        'email' => 'warehouse@example.com',
        'country' => 'Tajikistan',
        'state' => 'Districts of Republican Subordination',
        'city' => 'Dushanbe',
        'address' => '92 Rudaki Avenue',
        'postal_code' => '734000',
        'latitude' => '38.5598',
        'longitude' => '68.7870',
        'status' => Warehouse::STATUS_ACTIVE,
        'notes' => 'Main distribution hub',
    ];
}

it('shows warehouse list page to authenticated admins', function () {
    $admin = makeWarehouseAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.warehouses.index'))
        ->assertOk()
        ->assertSee('Warehouse List');
});

it('creates a warehouse with validation', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set('warehouse_name', '')
        ->call('save')
        ->assertHasErrors(['warehouse_name', 'warehouse_code', 'contact_person', 'contact_number', 'latitude', 'longitude']);

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    $warehouse = Warehouse::query()->first();

    expect($warehouse)
        ->warehouse_name->toBe('Dushanbe Central Warehouse')
        ->warehouse_code->toBe('DUS-TJ-01')
        ->country->toBe('Tajikistan')
        ->latitude->toBe('38.5598000')
        ->longitude->toBe('68.7870000')
        ->isActive()->toBeTrue();
});

it('enforces unique warehouse codes', function () {
    $admin = makeWarehouseAdmin();
    Warehouse::create(validWarehousePayload());

    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertHasErrors(['warehouse_code']);
});

it('updates and toggles warehouse status', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(validWarehousePayload());

    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseEditPage::class, ['warehouse' => $warehouse])
        ->set('warehouse_name', 'Updated Warehouse')
        ->set('city', 'Khujand')
        ->call('update')
        ->assertRedirect(route('admin.warehouses.show', $warehouse));

    expect($warehouse->fresh()->warehouse_name)->toBe('Updated Warehouse')
        ->and($warehouse->fresh()->city)->toBe('Khujand');

    Livewire::test(WarehouseListPage::class)
        ->call('toggleStatus', $warehouse->id);

    expect($warehouse->fresh()->status)->toBe(Warehouse::STATUS_INACTIVE);
});

it('soft deletes a warehouse from the list page', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(validWarehousePayload());

    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseListPage::class)
        ->set('deleteId', $warehouse->id)
        ->call('onConfirmed');

    expect(Warehouse::query()->count())->toBe(0)
        ->and(Warehouse::withTrashed()->count())->toBe(1);
});

it('shows warehouse details page', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(validWarehousePayload());

    $this->actingAs($admin, 'admin')
        ->get(route('admin.warehouses.show', $warehouse))
        ->assertOk()
        ->assertSee('Dushanbe Central Warehouse')
        ->assertSee('DUS-TJ-01')
        ->assertSee('38.5598000');
});
