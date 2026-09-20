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
        'role' => Admin::ROLE_SUPER_ADMIN,
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

function validWarehousePayload(): array
{
    return [
        'warehouse_name' => 'Dushanbe Central Warehouse',
        'contact_number' => '+992901234567',
        'login_username' => 'dus_tj_01',
        'login_email' => 'login.dus@example.com',
        'login_password' => 'password123',
        'login_password_confirmation' => 'password123',
        'address' => '92 Rudaki Avenue',
        'status' => Warehouse::STATUS_ACTIVE,
    ];
}

function warehouseRecordPayload(array $overrides = []): array
{
    return array_merge([
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
    ], $overrides);
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
        ->assertHasErrors(['warehouse_name', 'contact_number', 'address']);

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    $warehouse = Warehouse::query()->first();

    expect($warehouse)
        ->warehouse_name->toBe('Dushanbe Central Warehouse')
        ->address->toBe('92 Rudaki Avenue')
        ->contact_person->toBeNull()
        ->contact_number->toBe('+992901234567')
        ->email->toBeNull()
        ->city->toBeNull()
        ->latitude->toBeNull()
        ->longitude->toBeNull()
        ->isActive()->toBeTrue()
        ->and($warehouse->warehouse_code)->toStartWith('WH-')
        ->and(strlen($warehouse->warehouse_code))->toBe(11);
});

it('generates unique warehouse codes automatically', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->set('login_username', 'dus_tj_02')
        ->set('login_email', 'login2.dus@example.com')
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    $codes = Warehouse::query()->pluck('warehouse_code');

    expect($codes)->toHaveCount(2)
        ->and($codes[0])->not->toBe($codes[1])
        ->and($codes->every(fn (string $code) => str_starts_with($code, 'WH-')))->toBeTrue();
});

it('updates and toggles warehouse status', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(warehouseRecordPayload());

    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseEditPage::class, ['warehouse' => $warehouse])
        ->set('warehouse_name', 'Updated Warehouse')
        ->set('address', 'Updated Street')
        ->set('login_username', 'updated_wh')
        ->set('login_email', 'updated.login@example.com')
        ->set('login_password', 'password123')
        ->set('login_password_confirmation', 'password123')
        ->call('update')
        ->assertRedirect(route('admin.warehouses.show', $warehouse));

    expect($warehouse->fresh()->warehouse_name)->toBe('Updated Warehouse')
        ->and($warehouse->fresh()->address)->toBe('Updated Street')
        ->and($warehouse->fresh()->warehouse_code)->toBe('DUS-TJ-01')
        ->and($warehouse->fresh()->contact_number)->toBe('+992901234567')
        ->and($warehouse->fresh()->email)->toBe('warehouse@example.com')
        ->and($warehouse->fresh()->city)->toBe('Dushanbe')
        ->and($warehouse->fresh()->contact_person)->toBe('Rustam Karimov');

    Livewire::test(WarehouseListPage::class)
        ->call('toggleStatus', $warehouse->id);

    expect($warehouse->fresh()->status)->toBe(Warehouse::STATUS_INACTIVE);
});

it('soft deletes a warehouse from the list page', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(warehouseRecordPayload());

    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseListPage::class)
        ->set('deleteId', $warehouse->id)
        ->call('onConfirmed');

    expect(Warehouse::query()->count())->toBe(0)
        ->and(Warehouse::withTrashed()->count())->toBe(1);
});

it('shows warehouse details page', function () {
    $admin = makeWarehouseAdmin();
    $warehouse = Warehouse::create(warehouseRecordPayload());

    $this->actingAs($admin, 'admin')
        ->get(route('admin.warehouses.show', $warehouse))
        ->assertOk()
        ->assertSee('Dushanbe Central Warehouse')
        ->assertDontSee('DUS-TJ-01')
        ->assertSee('38.5598000');
});

it('persists default working hours when creating a warehouse', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    $warehouse = Warehouse::query()->first();
    $hours = $warehouse->workingHours()->orderBy('day_of_week')->get();

    expect($hours)->toHaveCount(7)
        ->and($hours->firstWhere('day_of_week', 1)->is_closed)->toBeFalse()
        ->and($hours->firstWhere('day_of_week', 1)->opens_at)->toStartWith('09:00')
        ->and($hours->firstWhere('day_of_week', 1)->closes_at)->toStartWith('19:00')
        ->and($hours->firstWhere('day_of_week', 1)->break_starts_at)->toStartWith('12:40')
        ->and($hours->firstWhere('day_of_week', 1)->break_ends_at)->toStartWith('14:00')
        ->and($hours->firstWhere('day_of_week', 7)->is_closed)->toBeTrue();
});

it('updates working hours and shows them on the details page', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save');

    $warehouse = Warehouse::query()->first();

    $component = Livewire::test(WarehouseEditPage::class, ['warehouse' => $warehouse]);
    $hours = $component->get('workingHours');

    foreach ($hours as $key => $row) {
        if ((int) $row['day_of_week'] === 1) {
            $hours[$key]['opens_at'] = '08:30';
            $hours[$key]['closes_at'] = '18:00';
            $hours[$key]['break_starts_at'] = '13:00';
            $hours[$key]['break_ends_at'] = '14:00';
        }

        if ((int) $row['day_of_week'] === 6) {
            $hours[$key]['is_closed'] = true;
            $hours[$key]['opens_at'] = '';
            $hours[$key]['closes_at'] = '';
            $hours[$key]['break_starts_at'] = '';
            $hours[$key]['break_ends_at'] = '';
        }
    }

    $component
        ->set('workingHours', $hours)
        ->call('update')
        ->assertRedirect(route('admin.warehouses.show', $warehouse));

    $monday = $warehouse->fresh()->workingHours()->where('day_of_week', 1)->first();
    $saturday = $warehouse->fresh()->workingHours()->where('day_of_week', 6)->first();

    expect($monday->opens_at)->toStartWith('08:30')
        ->and($monday->closes_at)->toStartWith('18:00')
        ->and($monday->break_starts_at)->toStartWith('13:00')
        ->and($saturday->is_closed)->toBeTrue();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.warehouses.show', $warehouse))
        ->assertOk()
        ->assertSee(__('admin.working_hours'))
        ->assertSee('08:30')
        ->assertSee('18:00')
        ->assertSee(__('admin.break'))
        ->assertSee(__('admin.weekday_7').': '.__('admin.closed'));
});
