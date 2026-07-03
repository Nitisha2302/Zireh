<?php

use App\Livewire\Admin\PlatformCommissionSlab\PlatformCommissionSlabCreatePage;
use App\Models\Admin;
use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeCommissionSlabAdmin(): Admin
{
    return Admin::create([
        'name' => 'Commission Admin',
        'username' => 'commissionadmin',
        'email' => 'commission@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

function makeCommissionPlatform(): Platform
{
    return Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao', 'ru' => 'Taobao', 'tg' => 'Taobao'],
        'logo' => [],
        'is_available' => true,
    ]);
}

it('shows validation errors on commission slab create form', function () {
    $admin = makeCommissionSlabAdmin();
    $platform = makeCommissionPlatform();

    $this->actingAs($admin, 'admin');

    Livewire::test(PlatformCommissionSlabCreatePage::class, ['platform' => $platform])
        ->set('minAmount', '')
        ->set('commissionPercentage', '')
        ->call('save')
        ->assertHasErrors(['minAmount', 'commissionPercentage']);
});

it('shows overlap validation error on commission slab create form', function () {
    $admin = makeCommissionSlabAdmin();
    $platform = makeCommissionPlatform();

    PlatformCommissionSlab::create([
        'platform_id' => $platform->id,
        'min_amount' => 0,
        'max_amount' => 100,
        'commission_percentage' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(PlatformCommissionSlabCreatePage::class, ['platform' => $platform])
        ->set('minAmount', '50')
        ->set('maxAmount', '150')
        ->set('commissionPercentage', '10')
        ->call('save')
        ->assertHasErrors(['minAmount']);
});
