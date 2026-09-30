<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\AlifApiSettingsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Support\Alif\AlifProviderConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeAlifSettingsAdmin(): Admin
{
    return Admin::create([
        'name' => 'Alif Settings Admin',
        'username' => 'alifsettingsadmin',
        'email' => 'alifsettings@example.com',
        'password' => Hash::make('secret-password'),
        'role' => Admin::ROLE_SUPER_ADMIN,
        'email_verified_at' => now(),
    ]);
}

beforeEach(function () {
    config([
        'alif.login' => 'env-login',
        'alif.password' => 'env-password',
        'alif.min_amount' => '1.00',
        'alif.max_amount' => '20000.00',
        'alif.currency' => 'TJS',
        'alif.srv_id' => null,
    ]);

    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();
});

it('shows the alif settings page to authenticated admins', function () {
    $admin = makeAlifSettingsAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.alif'))
        ->assertOk()
        ->assertSee('Alif Settings')
        ->assertSee('/api/alif');
});

it('saves alif settings and they override env configuration', function () {
    $admin = makeAlifSettingsAdmin();

    $this->actingAs($admin, 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_login', 'portal-login')
        ->set('alif_password', 'portal-secret')
        ->set('alif_srv_id', 'wallet-topup')
        ->set('alif_min_amount', '5.00')
        ->set('alif_max_amount', '100.50')
        ->set('alif_currency', 'tjs')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('alif_password', '');

    $config = app(AlifProviderConfig::class);

    expect(SettingHelper::get(AlifProviderConfig::SETTING_LOGIN))->toBe('portal-login')
        ->and(SettingHelper::get(AlifProviderConfig::SETTING_PASSWORD))->toBe('portal-secret')
        ->and(SettingHelper::get(AlifProviderConfig::SETTING_SRV_ID))->toBe('wallet-topup')
        ->and($config->login())->toBe('portal-login')
        ->and($config->password())->toBe('portal-secret')
        ->and($config->srvId())->toBe('wallet-topup')
        ->and($config->minAmount()->value())->toBe('5.00')
        ->and($config->maxAmount()->value())->toBe('100.50')
        ->and($config->currency())->toBe('TJS');
});

it('keeps existing password when admin saves without entering a new one', function () {
    $admin = makeAlifSettingsAdmin();

    Setting::query()->create(['key' => AlifProviderConfig::SETTING_LOGIN, 'value' => 'keep-login']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_PASSWORD, 'value' => 'keep-password']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_MIN_AMOUNT, 'value' => '1.00']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_MAX_AMOUNT, 'value' => '20000.00']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_CURRENCY, 'value' => 'TJS']);
    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();

    $this->actingAs($admin, 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_login', 'updated-login')
        ->set('alif_password', '')
        ->set('alif_min_amount', '2.00')
        ->set('alif_max_amount', '500.00')
        ->set('alif_currency', 'TJS')
        ->call('save')
        ->assertHasNoErrors();

    expect(SettingHelper::get(AlifProviderConfig::SETTING_PASSWORD))->toBe('keep-password')
        ->and(SettingHelper::get(AlifProviderConfig::SETTING_LOGIN))->toBe('updated-login')
        ->and(app(AlifProviderConfig::class)->password())->toBe('keep-password');
});

it('does not echo the stored password into the form', function () {
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_LOGIN, 'value' => 'portal-login']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_PASSWORD, 'value' => 'never-show-this']);
    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();

    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->assertSet('alif_login', 'portal-login')
        ->assertSet('alif_password', '')
        ->assertSet('passwordConfigured', true)
        ->assertDontSee('never-show-this');
});

it('rejects a minimum amount greater than the maximum', function () {
    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_login', 'portal-login')
        ->set('alif_password', 'portal-secret')
        ->set('alif_min_amount', '100.00')
        ->set('alif_max_amount', '10.00')
        ->set('alif_currency', 'TJS')
        ->call('save')
        ->assertHasErrors(['alif_max_amount']);
});
