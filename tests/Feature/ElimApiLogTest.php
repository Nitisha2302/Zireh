<?php

use App\Livewire\Admin\Settings\ElimApiLogDetailPage;
use App\Livewire\Admin\Settings\ElimApiLogListPage;
use App\Models\Admin;
use App\Models\ElimApiLog;
use App\Services\Elim\ElimApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.elim.base_url' => 'https://openapi.elim.asia']);
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);
});

function makeElimLogAdmin(): Admin
{
    return Admin::create([
        'name' => 'Log Admin',
        'username' => 'logadmin',
        'email' => 'logadmin@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

it('persists elim api client request and response logs', function () {
    Http::fake([
        'https://openapi.elim.asia/v1/orders/ORD0000000001' => Http::response([
            'data' => ['id' => 'ORD0000000001', 'status' => 'paid'],
        ], 200),
    ]);

    app(ElimApiClient::class)->get('/v1/orders/ORD0000000001');

    expect(ElimApiLog::query()->count())->toBe(1)
        ->and(ElimApiLog::first()->method)->toBe('GET')
        ->and(ElimApiLog::first()->endpoint)->toBe('/v1/orders/ORD0000000001')
        ->and(ElimApiLog::first()->is_successful)->toBeTrue()
        ->and(ElimApiLog::first()->response_body)->toBeArray();
});

it('redacts sensitive fields in request logs', function () {
    Http::fake([
        'https://openapi.elim.asia/v1/auth/login' => Http::response([
            'access_token' => 'secret-access',
            'refresh_token' => 'secret-refresh',
        ], 200),
    ]);

    config([
        'services.elim.email' => 'api@example.com',
        'services.elim.password' => 'super-secret',
    ]);

    app(\App\Services\Elim\ElimAuthService::class)->login();

    $log = ElimApiLog::query()->first();

    expect($log->request_payload['password'])->toBe('[REDACTED]')
        ->and($log->response_body['access_token'])->toBe('[REDACTED]');
});

it('shows elim api logs page to admins', function () {
    $admin = makeElimLogAdmin();
    $this->actingAs($admin, 'admin');

    ElimApiLog::create([
        'method' => 'POST',
        'endpoint' => '/v1/orders/preview',
        'source' => ElimApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
        'duration_ms' => 120,
        'request_payload' => ['platform' => 'taobao'],
        'response_body' => ['data' => ['goods_amount_cny' => 10]],
    ]);

    $this->get(route('admin.settings.elim-api-logs.index'))
        ->assertOk()
        ->assertSee('Elim API Logs')
        ->assertSee('/v1/orders/preview');
});

it('shows elim api log detail page', function () {
    $admin = makeElimLogAdmin();
    $this->actingAs($admin, 'admin');

    $log = ElimApiLog::create([
        'method' => 'GET',
        'endpoint' => '/v1/orders/ORD0000000001',
        'source' => ElimApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
        'request_payload' => null,
        'response_body' => ['data' => ['status' => 'shipped']],
    ]);

    Livewire::test(ElimApiLogDetailPage::class, ['log' => $log])
        ->assertSee('/v1/orders/ORD0000000001')
        ->assertSee('shipped');
});

it('allows admin to purge old elim api logs', function () {
    $admin = makeElimLogAdmin();
    $this->actingAs($admin, 'admin');

    $old = ElimApiLog::create([
        'method' => 'GET',
        'endpoint' => '/v1/old',
        'source' => ElimApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
    ]);
    $old->forceFill([
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ])->saveQuietly();

    ElimApiLog::create([
        'method' => 'GET',
        'endpoint' => '/v1/recent',
        'source' => ElimApiLog::SOURCE_API,
        'status_code' => 200,
        'is_successful' => true,
    ]);

    Livewire::test(ElimApiLogListPage::class)
        ->call('clearOldLogs');

    expect(ElimApiLog::query()->count())->toBe(1)
        ->and(ElimApiLog::first()->endpoint)->toBe('/v1/recent');
});
