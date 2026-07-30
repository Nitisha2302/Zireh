<?php

use App\Livewire\Admin\Settings\RapidApiLogDetailPage;
use App\Livewire\Admin\Settings\RapidApiLogListPage;
use App\Models\Admin;
use App\Models\RapidApiLog;
use App\Services\RapidApi\RapidApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.rapidapi.key' => 'test-rapidapi-key',
        'services.rapidapi.host' => 'jd-com-product-reviews-data-api.p.rapidapi.com',
        'services.rapidapi.base_url' => 'https://jd-com-product-reviews-data-api.p.rapidapi.com',
    ]);
});

function makeRapidApiLogAdmin(): Admin
{
    return Admin::create([
        'name' => 'Rapid Log Admin',
        'username' => 'rapidlogadmin',
        'email' => 'rapidlogadmin@example.com',
        'password' => Hash::make('secret-password'),
    ]);
}

it('persists rapidapi client request and response logs', function () {
    Http::fake([
        'jd-com-product-reviews-data-api.p.rapidapi.com/jd/product-search*' => Http::response([
            'ok' => true,
            'count' => 1,
            'data' => [['itemId' => '100256400499', 'productTitle' => 'Test']],
        ], 200),
    ]);

    app(RapidApiClient::class)->get('/jd/product-search', [
        'keyword' => '华为手机',
        'page' => 1,
    ]);

    expect(RapidApiLog::query()->count())->toBe(1)
        ->and(RapidApiLog::first()->method)->toBe('GET')
        ->and(RapidApiLog::first()->endpoint)->toBe('/jd/product-search')
        ->and(RapidApiLog::first()->is_successful)->toBeTrue()
        ->and(RapidApiLog::first()->request_payload)->toMatchArray([
            'keyword' => '华为手机',
            'page' => 1,
        ])
        ->and(RapidApiLog::first()->response_body)->toBeArray();
});

it('persists failed rapidapi requests before throwing', function () {
    Http::fake([
        'jd-com-product-reviews-data-api.p.rapidapi.com/jd/product-price*' => Http::response([
            'message' => 'Upstream failed',
        ], 500),
    ]);

    expect(fn () => app(RapidApiClient::class)->get('/jd/product-price', ['itemId' => '1']))
        ->toThrow(\App\Exceptions\RapidApi\RapidApiRequestException::class);

    expect(RapidApiLog::query()->count())->toBe(1)
        ->and(RapidApiLog::first()->is_successful)->toBeFalse()
        ->and(RapidApiLog::first()->status_code)->toBe(500)
        ->and(RapidApiLog::first()->error_message)->toBe('Upstream failed');
});

it('shows rapidapi logs page to admins', function () {
    $admin = makeRapidApiLogAdmin();
    $this->actingAs($admin, 'admin');

    RapidApiLog::create([
        'method' => 'GET',
        'endpoint' => '/jd/product-search',
        'source' => RapidApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
        'duration_ms' => 120,
        'request_payload' => ['keyword' => '手机'],
        'response_body' => ['ok' => true],
    ]);

    Livewire::test(RapidApiLogListPage::class)
        ->assertSee('RapidAPI Logs')
        ->assertSee('/jd/product-search');
});

it('shows rapidapi log detail page', function () {
    $admin = makeRapidApiLogAdmin();
    $this->actingAs($admin, 'admin');

    $log = RapidApiLog::create([
        'method' => 'GET',
        'endpoint' => '/jd/product-price',
        'source' => RapidApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
        'request_payload' => ['itemId' => '100256400499'],
        'response_body' => ['data' => [['price' => 6499]]],
    ]);

    Livewire::test(RapidApiLogDetailPage::class, ['log' => $log])
        ->assertSee('/jd/product-price')
        ->assertSee('6499');
});

it('allows admin to purge old rapidapi logs', function () {
    $admin = makeRapidApiLogAdmin();
    $this->actingAs($admin, 'admin');

    $old = RapidApiLog::create([
        'method' => 'GET',
        'endpoint' => '/jd/product-old',
        'source' => RapidApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
    ]);
    $old->forceFill([
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ])->saveQuietly();

    RapidApiLog::create([
        'method' => 'GET',
        'endpoint' => '/jd/product-recent',
        'source' => RapidApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
    ]);

    Livewire::test(RapidApiLogListPage::class)
        ->call('clearOldLogs');

    expect(RapidApiLog::query()->count())->toBe(1)
        ->and(RapidApiLog::first()->endpoint)->toBe('/jd/product-recent');
});
