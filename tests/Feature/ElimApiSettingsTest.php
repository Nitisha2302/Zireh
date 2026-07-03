<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\ElimApiSettingsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Support\Elim\ElimApiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeElimSettingsAdmin(): Admin
{
    return Admin::create([
        'name' => 'Elim Admin',
        'username' => 'elimadmin',
        'email' => 'elim@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

it('shows the elim api settings page to authenticated admins', function () {
    $admin = makeElimSettingsAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.elim-api'))
        ->assertOk()
        ->assertSee('Elim API Settings');
});

it('saves elim api settings and refreshes cached configuration', function () {
    $admin = makeElimSettingsAdmin();
    config([
        'services.elim.base_url' => 'https://openapi.elim.asia',
        'services.elim.email' => 'env@example.com',
        'services.elim.password' => 'env-password',
    ]);

    Setting::create(['key' => ElimApiConfig::SETTING_EMAIL, 'value' => 'old@example.com']);
    Setting::create(['key' => ElimApiConfig::SETTING_PASSWORD, 'value' => 'old-password']);

    $config = app(ElimApiConfig::class);
    expect($config->email())->toBe('old@example.com');

    $this->actingAs($admin, 'admin');

    Livewire::test(ElimApiSettingsPage::class)
        ->set('elim_base_url', 'https://api.elim.test')
        ->set('elim_email', 'new@example.com')
        ->set('elim_password', 'new-password')
        ->call('save');

    SettingHelper::clearCache();
    $config->clearCache();

    expect(SettingHelper::get(ElimApiConfig::SETTING_EMAIL))->toBe('new@example.com')
        ->and(SettingHelper::get(ElimApiConfig::SETTING_PASSWORD))->toBe('new-password')
        ->and($config->email())->toBe('new@example.com')
        ->and($config->password())->toBe('new-password')
        ->and($config->baseUrl())->toBe('https://api.elim.test')
        ->and(Cache::has('elim:auth:access_token'))->toBeFalse();
});

it('keeps existing password when admin saves without entering a new one', function () {
    $admin = makeElimSettingsAdmin();

    Setting::create(['key' => ElimApiConfig::SETTING_BASE_URL, 'value' => 'https://openapi.elim.asia']);
    Setting::create(['key' => ElimApiConfig::SETTING_EMAIL, 'value' => 'keep@example.com']);
    Setting::create(['key' => ElimApiConfig::SETTING_PASSWORD, 'value' => 'keep-password']);

    $this->actingAs($admin, 'admin');

    Livewire::test(ElimApiSettingsPage::class)
        ->set('elim_base_url', 'https://openapi.elim.asia')
        ->set('elim_email', 'updated@example.com')
        ->set('elim_password', '')
        ->call('save');

    expect(SettingHelper::get(ElimApiConfig::SETTING_PASSWORD))->toBe('keep-password')
        ->and(SettingHelper::get(ElimApiConfig::SETTING_EMAIL))->toBe('updated@example.com');
});

it('reports success when elim api credentials test passes', function () {
    $admin = makeElimSettingsAdmin();

    Http::fake([
        'https://openapi.elim.asia/v1/auth/login' => Http::response([
            'access_token' => 'test-token',
            'refresh_token' => 'refresh-token',
        ], 200),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(ElimApiSettingsPage::class)
        ->set('elim_base_url', 'https://openapi.elim.asia')
        ->set('elim_email', 'valid@example.com')
        ->set('elim_password', 'valid-password')
        ->call('testConnection')
        ->assertHasNoErrors();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://openapi.elim.asia/v1/auth/login'
            && $request['email'] === 'valid@example.com'
            && $request['password'] === 'valid-password';
    });

    expect(Cache::has('elim:auth:access_token'))->toBeFalse();
});

it('reports failure when elim api credentials test fails', function () {
    $admin = makeElimSettingsAdmin();

    Http::fake([
        'https://openapi.elim.asia/v1/auth/login' => Http::response([
            'message' => 'Invalid credentials',
        ], 401),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(ElimApiSettingsPage::class)
        ->set('elim_base_url', 'https://openapi.elim.asia')
        ->set('elim_email', 'bad@example.com')
        ->set('elim_password', 'wrong-password')
        ->call('testConnection')
        ->assertHasNoErrors();
});

it('uses stored password for test when password field is left blank', function () {
    $admin = makeElimSettingsAdmin();

    Setting::create(['key' => ElimApiConfig::SETTING_PASSWORD, 'value' => 'stored-password']);

    Http::fake([
        'https://openapi.elim.asia/v1/auth/login' => Http::response([
            'access_token' => 'test-token',
        ], 200),
    ]);

    $this->actingAs($admin, 'admin');

    Livewire::test(ElimApiSettingsPage::class)
        ->set('elim_base_url', 'https://openapi.elim.asia')
        ->set('elim_email', 'valid@example.com')
        ->set('elim_password', '')
        ->call('testConnection')
        ->assertHasNoErrors();

    Http::assertSent(fn ($request) => $request['password'] === 'stored-password');
});
