<?php

use App\Livewire\Admin\Settings\CurrencyExchangeSettingsPage;
use App\Models\Admin;
use App\Models\CurrencyExchangeRate;
use App\Services\Currency\CurrencyExchangeService;
use App\Services\Currency\ExchangeRateService;
use App\Support\Currency\CurrencyPriceConverter;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeExchangeRateAdmin(): Admin
{
    return Admin::create([
        'name' => 'Exchange Admin',
        'username' => 'exchangeadmin',
        'email' => 'exchange@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

it('converts cny amounts to tjs using database exchange rate', function () {
    CurrencyExchangeRate::create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 2,
        'auto_refresh_enabled' => false,
        'refresh_interval_hours' => 1,
    ]);

    app(CurrencyExchangeService::class)->clearCache();

    expect(app(CurrencyExchangeService::class)->convertCnyToTjs(10))->toBe(20.0)
        ->and(app(CurrencyPriceConverter::class)->applyToProductListItem(['price' => 5])['price_tjs'])->toBe(10.0);
});

it('refreshes exchange rate from api and stores last synced timestamp', function () {
    Http::fake([
        '*' => Http::response([
            'result' => 'success',
            'rates' => ['TJS' => 1.75],
        ], 200),
    ]);

    $config = app(CurrencyExchangeService::class)->refresh(manual: true);

    expect((float) $config->exchange_rate)->toBe(1.75)
        ->and($config->last_synced_at)->not->toBeNull();
});

it('skips automatic refresh until interval has elapsed', function () {
    Http::fake();

    CurrencyExchangeRate::create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 1.5,
        'auto_refresh_enabled' => true,
        'refresh_interval_hours' => 2,
        'last_synced_at' => now(),
    ]);

    expect(app(CurrencyExchangeService::class)->refreshIfDue())->toBeFalse();
    Http::assertNothingSent();
});

it('shows currency exchange settings page to authenticated admins', function () {
    $admin = makeExchangeRateAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.currency-exchange'))
        ->assertOk()
        ->assertSee('Currency Exchange Rate');
});

it('saves exchange rate settings from admin page', function () {
    $admin = makeExchangeRateAdmin();
    $this->actingAs($admin, 'admin');

    Livewire::test(CurrencyExchangeSettingsPage::class)
        ->set('exchangeRate', '1.85')
        ->set('autoRefreshEnabled', true)
        ->set('refreshIntervalHours', 3)
        ->call('save')
        ->assertHasNoErrors();

    $config = CurrencyExchangeRate::query()->first();

    expect((float) $config->exchange_rate)->toBe(1.85)
        ->and($config->auto_refresh_enabled)->toBeTrue()
        ->and($config->refresh_interval_hours)->toBe(3);
});

it('loads active config even when legacy cache contains a serialized model', function () {
    CurrencyExchangeRate::create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 2,
        'auto_refresh_enabled' => false,
        'refresh_interval_hours' => 1,
    ]);

    Cache::forever(CurrencyExchangeService::CACHE_KEY, unserialize('O:8:"stdClass":0:{}'));

    $config = app(CurrencyExchangeService::class)->getActive();

    expect($config)->toBeInstanceOf(CurrencyExchangeRate::class)
        ->and((float) $config->exchange_rate)->toBe(2.0);
});

it('adds tjs prices to product normalizer output', function () {
    CurrencyExchangeRate::create([
        'from_currency' => 'CNY',
        'to_currency' => 'TJS',
        'exchange_rate' => 2,
        'auto_refresh_enabled' => false,
        'refresh_interval_hours' => 1,
    ]);

    app(CurrencyExchangeService::class)->clearCache();

    $normalizer = app(ProductNormalizer::class);
    $detail = $normalizer->detailResponse([
        'id' => '123',
        'title' => 'Sample',
        'price' => 10,
        'promotion_price' => 8,
        'skus' => [
            ['id' => '1', 'price' => 10],
        ],
    ], 'taobao');

    expect($detail['price_tjs'])->toBe(20.0)
        ->and($detail['promotion_price_tjs'])->toBe(16.0)
        ->and($detail['currency']['exchange_rate'])->toBe(2.0);
});
