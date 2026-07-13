<?php

use App\Helpers\SettingHelper;
use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\CompanySettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds and caches company name for branding helpers', function () {
    $this->seed(CompanySettingsSeeder::class);

    expect(company_name())->toBe('ZirehCargo')
        ->and(SettingHelper::company()['name'])->toBe('ZirehCargo')
        ->and(company_logo_url())->toBeNull();

    Setting::updateOrCreate(['key' => 'company_name'], ['value' => 'New Cargo Co']);
    SettingHelper::clearCache();

    expect(company_name())->toBe('New Cargo Co');
});

it('exposes company settings page to super admin', function () {
    $this->seed(CompanySettingsSeeder::class);

    $admin = Admin::create([
        'name' => 'Company Admin',
        'username' => 'companyadmin',
        'email' => 'company@example.com',
        'role' => Admin::ROLE_SUPER_ADMIN,
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.company'))
        ->assertOk()
        ->assertSee(__('admin.company_settings'), false);
});
