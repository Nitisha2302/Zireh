<?php

use App\Models\Admin;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSuperAdmin(): Admin
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

it('allows super admin to manage warehouse staff', function () {
    $superAdmin = makeSuperAdmin();
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-STAFF',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000099',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Street 1',
        'latitude' => 38.55,
        'longitude' => 68.78,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.warehouse-staff.index'))
        ->assertOk();

    Livewire::actingAs($superAdmin, 'admin')
        ->test(\App\Livewire\Admin\WarehouseStaff\WarehouseStaffCreatePage::class)
        ->set('name', 'TJ Staff')
        ->set('username', 'tj_staff_user')
        ->set('email', 'tjstaff@example.com')
        ->set('role', Admin::ROLE_TAJIKISTAN_WAREHOUSE)
        ->set('warehouse_id', $warehouse->id)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertRedirect(route('admin.warehouse-staff.index'));

    $staff = Admin::query()->where('username', 'tj_staff_user')->first();
    expect($staff)->not->toBeNull()
        ->and($staff->role)->toBe(Admin::ROLE_TAJIKISTAN_WAREHOUSE)
        ->and($staff->warehouse_id)->toBe($warehouse->id);

    Livewire::actingAs($superAdmin, 'admin')
        ->test(\App\Livewire\Admin\WarehouseStaff\WarehouseStaffEditPage::class, ['admin' => $staff])
        ->set('name', 'TJ Staff Updated')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('save')
        ->assertRedirect(route('admin.warehouse-staff.index'));

    expect($staff->fresh()->name)->toBe('TJ Staff Updated');
});

it('blocks warehouse staff from warehouse staff management', function () {
    $staff = Admin::create([
        'name' => 'China Staff',
        'username' => 'china_staff',
        'email' => 'china@example.com',
        'role' => Admin::ROLE_CHINA_WAREHOUSE,
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($staff, 'admin')
        ->get(route('admin.warehouse-staff.index'))
        ->assertForbidden();
});
