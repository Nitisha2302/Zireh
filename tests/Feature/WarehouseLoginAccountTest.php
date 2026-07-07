<?php

use App\Livewire\Admin\Settings\ChinaWarehouseLoginSettingsPage;
use App\Livewire\Admin\Warehouse\WarehouseCreatePage;
use App\Livewire\Admin\Warehouse\WarehouseEditPage;
use App\Livewire\Admin\Warehouse\WarehouseListPage;
use App\Livewire\Warehouse\Tajikistan\LoginPage as TajikistanLoginPage;
use App\Models\Admin;
use App\Models\Warehouse;
use App\Services\Admin\WarehouseLoginAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeWarehouseAdmin(): Admin
{
    return Admin::create([
        'name' => 'Super Admin',
        'username' => 'superadmin',
        'email' => 'super@example.com',
        'role' => Admin::ROLE_SUPER_ADMIN,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);
}

function validWarehousePayload(array $overrides = []): array
{
    return array_merge([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-TEST-01',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000001',
        'email' => 'contact@warehouse.example.com',
        'login_username' => 'dushanbe_wh',
        'login_email' => 'login@warehouse.example.com',
        'login_password' => 'password123',
        'login_password_confirmation' => 'password123',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 1',
        'postal_code' => '',
        'latitude' => '38.55',
        'longitude' => '68.78',
        'status' => Warehouse::STATUS_ACTIVE,
        'notes' => '',
    ], $overrides);
}

it('creates warehouse with embedded tajikistan login account', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save')
        ->assertRedirect(route('admin.warehouses.index'));

    $warehouse = Warehouse::query()->where('warehouse_code', 'DUS-TEST-01')->first();
    $account = app(WarehouseLoginAccountService::class)->findTajikistanAccount($warehouse);

    expect($warehouse)->not->toBeNull()
        ->and($account)->not->toBeNull()
        ->and($account->username)->toBe('dushanbe_wh')
        ->and($account->email)->toBe('login@warehouse.example.com')
        ->and($account->warehouse_id)->toBe($warehouse->id)
        ->and($account->role)->toBe(Admin::ROLE_TAJIKISTAN_WAREHOUSE);
});

it('updates warehouse login credentials on edit', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save');

    $warehouse = Warehouse::query()->where('warehouse_code', 'DUS-TEST-01')->first();

    Livewire::test(WarehouseEditPage::class, ['warehouse' => $warehouse])
        ->set('login_username', 'dushanbe_updated')
        ->set('login_email', 'updated@warehouse.example.com')
        ->set('login_password', 'newpassword123')
        ->set('login_password_confirmation', 'newpassword123')
        ->call('update')
        ->assertRedirect(route('admin.warehouses.show', $warehouse));

    $account = app(WarehouseLoginAccountService::class)->findTajikistanAccount($warehouse->fresh());

    expect($account->username)->toBe('dushanbe_updated')
        ->and($account->email)->toBe('updated@warehouse.example.com');
});

it('allows tajikistan login with warehouse credentials', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload([
            'login_username' => 'tj_login_user',
            'login_email' => 'tjlogin@example.com',
        ]))
        ->call('save');

    Livewire::test(TajikistanLoginPage::class)
        ->set('login', 'tjlogin@example.com')
        ->set('password', 'password123')
        ->call('authenticate')
        ->assertRedirect(route('tajikistan.orders.index'));
});

it('deletes linked login account when warehouse is deleted', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload())
        ->call('save');

    $warehouse = Warehouse::query()->where('warehouse_code', 'DUS-TEST-01')->first();
    $accountId = app(WarehouseLoginAccountService::class)->findTajikistanAccount($warehouse)->id;

    Livewire::test(WarehouseListPage::class)
        ->call('delete', $warehouse->id)
        ->call('onConfirmed');

    expect(Admin::query()->find($accountId))->toBeNull()
        ->and(Admin::onlyTrashed()->find($accountId))->not->toBeNull();
});

it('rejects login for inactive warehouse', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(WarehouseCreatePage::class)
        ->set(validWarehousePayload([
            'login_username' => 'inactive_wh',
            'login_email' => 'inactive@example.com',
            'status' => Warehouse::STATUS_INACTIVE,
        ]))
        ->call('save');

    Livewire::test(TajikistanLoginPage::class)
        ->set('login', 'inactive@example.com')
        ->set('password', 'password123')
        ->call('authenticate')
        ->assertHasErrors('login');
});

it('saves china warehouse login from settings page', function () {
    $admin = makeWarehouseAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(ChinaWarehouseLoginSettingsPage::class)
        ->set('login_username', 'china_wh_user')
        ->set('login_email', 'china@example.com')
        ->set('login_password', 'password123')
        ->set('login_password_confirmation', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $account = app(WarehouseLoginAccountService::class)->findChinaAccount();

    expect($account)->not->toBeNull()
        ->and($account->username)->toBe('china_wh_user')
        ->and($account->email)->toBe('china@example.com')
        ->and($account->role)->toBe(Admin::ROLE_CHINA_WAREHOUSE);
});
