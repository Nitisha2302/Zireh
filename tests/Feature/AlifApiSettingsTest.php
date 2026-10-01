<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\AlifApiSettingsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Support\Alif\AlifAcquiringConfig;
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
        'app.url' => 'https://app.test',
        'alif.terminal_key' => 'env-key',
        'alif.terminal_password' => 'env-password',
        'alif.base_url' => 'https://test-web.alif.tj',
        'alif.gate' => 'korti_milli',
        'alif.callback_url' => 'https://app.test/api/v1/payments/alif/callback',
        'alif.return_url' => 'https://app.test/payment/alif/return',
        'alif.min_amount' => '1.00',
        'alif.max_amount' => '20000.00',
        'alif.currency' => 'TJS',
    ]);

    SettingHelper::clearCache();
    app(AlifAcquiringConfig::class)->clearCache();
});

it('shows the alif settings page to authenticated admins', function () {
    $this->actingAs(makeAlifSettingsAdmin(), 'admin')
        ->get(route('admin.settings.alif'))
        ->assertOk()
        ->assertSee('Alif Settings')
        ->assertSee('Terminal key');
});

it('saves alif acquiring settings and they override env configuration', function () {
    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_terminal_key', 'admin-key')
        ->set('alif_terminal_password', 'admin-secret')
        ->set('alif_base_url', 'https://web.alif.tj')
        ->set('alif_gate', 'wallet')
        ->set('alif_callback_url', 'https://app.test/api/v1/payments/alif/callback')
        ->set('alif_return_url', 'https://app.test/payment/alif/return')
        ->set('alif_min_amount', '5.00')
        ->set('alif_max_amount', '100.50')
        ->set('alif_currency', 'tjs')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('alif_terminal_password', '');

    $config = app(AlifAcquiringConfig::class);

    expect(SettingHelper::get(AlifAcquiringConfig::SETTING_TERMINAL_KEY))->toBe('admin-key')
        ->and(SettingHelper::get(AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD))->toBe('admin-secret')
        ->and($config->terminalKey())->toBe('admin-key')
        ->and($config->terminalPassword())->toBe('admin-secret')
        ->and($config->baseUrl())->toBe('https://web.alif.tj')
        ->and($config->gate())->toBe('wallet')
        ->and($config->minAmount()->value())->toBe('5.00')
        ->and($config->maxAmount()->value())->toBe('100.50')
        ->and($config->currency())->toBe('TJS');
});

it('keeps existing password when admin saves without entering a new one', function () {
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_KEY, 'value' => 'keep-key']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD, 'value' => 'keep-password']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_BASE_URL, 'value' => 'https://test-web.alif.tj']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_GATE, 'value' => 'korti_milli']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_CALLBACK_URL, 'value' => 'https://app.test/api/v1/payments/alif/callback']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_RETURN_URL, 'value' => 'https://app.test/payment/alif/return']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_MIN_AMOUNT, 'value' => '1.00']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_MAX_AMOUNT, 'value' => '20000.00']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_CURRENCY, 'value' => 'TJS']);
    SettingHelper::clearCache();
    app(AlifAcquiringConfig::class)->clearCache();

    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_terminal_key', 'updated-key')
        ->set('alif_terminal_password', '')
        ->set('alif_base_url', 'https://test-web.alif.tj')
        ->set('alif_gate', 'korti_milli')
        ->set('alif_callback_url', 'https://app.test/api/v1/payments/alif/callback')
        ->set('alif_return_url', 'https://app.test/payment/alif/return')
        ->set('alif_min_amount', '2.00')
        ->set('alif_max_amount', '500.00')
        ->set('alif_currency', 'TJS')
        ->call('save')
        ->assertHasNoErrors();

    expect(SettingHelper::get(AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD))->toBe('keep-password')
        ->and(SettingHelper::get(AlifAcquiringConfig::SETTING_TERMINAL_KEY))->toBe('updated-key')
        ->and(app(AlifAcquiringConfig::class)->terminalPassword())->toBe('keep-password');
});

it('does not echo the stored terminal password into the form', function () {
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_KEY, 'value' => 'portal-key']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD, 'value' => 'never-show-this']);
    SettingHelper::clearCache();
    app(AlifAcquiringConfig::class)->clearCache();

    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->assertSet('alif_terminal_key', 'portal-key')
        ->assertSet('alif_terminal_password', '')
        ->assertSet('passwordConfigured', true)
        ->assertDontSee('never-show-this');
});

it('rejects a minimum amount greater than the maximum', function () {
    $this->actingAs(makeAlifSettingsAdmin(), 'admin');

    Livewire::test(AlifApiSettingsPage::class)
        ->set('alif_terminal_key', 'portal-key')
        ->set('alif_terminal_password', 'portal-secret')
        ->set('alif_base_url', 'https://test-web.alif.tj')
        ->set('alif_gate', 'korti_milli')
        ->set('alif_callback_url', 'https://app.test/api/v1/payments/alif/callback')
        ->set('alif_return_url', 'https://app.test/payment/alif/return')
        ->set('alif_min_amount', '100.00')
        ->set('alif_max_amount', '10.00')
        ->set('alif_currency', 'TJS')
        ->call('save')
        ->assertHasErrors(['alif_max_amount']);
});
