<?php

use App\Helpers\SettingHelper;
use App\Models\AlifApiLog;
use App\Models\AlifPayment;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Alif\AlifAcquiringService;
use App\Services\Wallet\WalletService;
use App\Support\Alif\AlifAcquiringConfig;
use App\Support\Logging\SensitiveKeyRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.url' => 'https://app.test',
        'alif.terminal_key' => '378222',
        'alif.terminal_password' => 'test-pass',
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

function alifAcquiringCustomer(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'phone' => '+992901234567',
        'status' => User::STATUS_ACTIVE,
    ], $overrides));
}

function fakeAlifInit(?array $json = null): void
{
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response($json ?? [
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
        'https://test-web.alif.tj/checktxn' => Http::response([
            'status' => 'ok',
            'transactionId' => 'TXN-1',
        ], 200),
    ]);
}

function alifCallbackToken(string $orderId, string $status, string $transactionId): string
{
    return app(AlifAcquiringService::class)->generateToken($orderId.$status.$transactionId);
}

it('inits an alif checkout and stores a pending payment', function () {
    fakeAlifInit();
    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount', '50.00')
        ->assertJsonPath('data.payment_url', 'https://pay.alif.test/checkout');

    $payment = AlifPayment::query()->firstOrFail();

    expect($payment->user_id)->toBe($user->id)
        ->and($payment->status)->toBe(AlifPayment::STATUS_PENDING)
        ->and($payment->amount)->toBe('50.00')
        ->and(app(WalletService::class)->getBalance($user))->toBe(0.0);
});

it('rejects init when the customer has no phone', function () {
    fakeAlifInit();
    Sanctum::actingAs(alifAcquiringCustomer(['phone' => '']));

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])
        ->assertStatus(422);

    expect(AlifPayment::query()->count())->toBe(0);
    Http::assertNothingSent();
});

it('rejects init amounts outside the configured range', function () {
    fakeAlifInit();
    Sanctum::actingAs(alifAcquiringCustomer());

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '0.50'])
        ->assertStatus(422);

    expect(AlifPayment::query()->count())->toBe(0);
});

it('credits the wallet once from a valid alif callback', function () {
    fakeAlifInit();
    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])
        ->json('data.order_id');

    $payload = [
        'orderId' => $orderId,
        'status' => 'ok',
        'transactionId' => 'TXN-99',
        'token' => alifCallbackToken($orderId, 'ok', 'TXN-99'),
    ];

    $this->postJson('/api/v1/payments/alif/callback', $payload)
        ->assertOk()
        ->assertJsonPath('ok', true);

    $this->postJson('/api/v1/payments/alif/callback', $payload)
        ->assertOk();

    expect(AlifPayment::query()->first()->status)->toBe(AlifPayment::STATUS_PAID)
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_ALIF_DEPOSIT)->count())->toBe(1)
        ->and(app(WalletService::class)->getBalance($user->fresh()))->toBe(50.0);
});

it('rejects a callback with an invalid token', function () {
    fakeAlifInit();
    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])
        ->json('data.order_id');

    $this->postJson('/api/v1/payments/alif/callback', [
        'orderId' => $orderId,
        'status' => 'ok',
        'transactionId' => 'TXN-99',
        'token' => 'not-a-valid-token',
    ])->assertStatus(400)->assertJsonPath('ok', false);

    expect(AlifPayment::query()->first()->status)->toBe(AlifPayment::STATUS_PENDING)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

it('credits from status poll when alif reports ok', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
        'https://test-web.alif.tj/checktxn' => Http::response([
            'status' => 'ok',
            'transactionId' => 'TXN-STATUS',
        ], 200),
    ]);

    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '25.00'])
        ->json('data.order_id');

    $this->getJson('/api/v1/auth/wallet/alif/status/'.$orderId)
        ->assertOk()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.transaction_id', 'TXN-STATUS');

    expect(app(WalletService::class)->getBalance($user->fresh()))->toBe(25.0);
});

it('signs callbacks with admin-saved terminal credentials instead of env', function () {
    fakeAlifInit();
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_KEY, 'value' => 'admin-key']);
    Setting::query()->create(['key' => AlifAcquiringConfig::SETTING_TERMINAL_PASSWORD, 'value' => 'admin-secret']);
    SettingHelper::clearCache();
    app(AlifAcquiringConfig::class)->clearCache();

    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '10.00'])
        ->json('data.order_id');

    $envToken = hash_hmac(
        'sha256',
        $orderId.'okTXN-ADMIN',
        hash_hmac('sha256', 'test-pass', '378222')
    );

    $this->postJson('/api/v1/payments/alif/callback', [
        'orderId' => $orderId,
        'status' => 'ok',
        'transactionId' => 'TXN-ADMIN',
        'token' => $envToken,
    ])->assertStatus(400);

    $this->postJson('/api/v1/payments/alif/callback', [
        'orderId' => $orderId,
        'status' => 'ok',
        'transactionId' => 'TXN-ADMIN',
        'token' => alifCallbackToken($orderId, 'ok', 'TXN-ADMIN'),
    ])->assertOk()->assertJsonPath('ok', true);

    expect(AlifPayment::query()->first()->isPaid())->toBeTrue();
});

it('stores the raw alif html error body on init failure', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response('<html>Bad Gateway from Alif</html>', 502, [
            'Content-Type' => 'text/html',
        ]),
    ]);

    Sanctum::actingAs(alifAcquiringCustomer());

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '100.00'])
        ->assertStatus(502);

    $log = AlifApiLog::query()->firstOrFail();

    expect($log->is_successful)->toBeFalse()
        ->and($log->http_status)->toBe(502)
        ->and($log->response_code)->toBe(502)
        ->and($log->error_message)->toBe('Invalid Alif response')
        ->and($log->response_body['http_status'] ?? null)->toBe(502)
        ->and($log->response_body['method'] ?? null)->toBe('POST')
        ->and($log->response_body['url'] ?? null)->toBe('https://test-web.alif.tj/v2/')
        ->and($log->response_body['body'] ?? null)->toContain('Bad Gateway from Alif')
        ->and($log->response_body['note'] ?? null)->toBe('Non-JSON Alif response')
        ->and($log->formattedResponseBody())->toContain('Bad Gateway from Alif');
});

it('stores http metadata when alif returns an empty 405 body', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response('', 405, [
            'Allow' => 'GET',
            'Content-Type' => 'text/html',
        ]),
    ]);

    Sanctum::actingAs(alifAcquiringCustomer());

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '100.00'])
        ->assertStatus(502);

    $log = AlifApiLog::query()->firstOrFail();

    expect($log->is_successful)->toBeFalse()
        ->and($log->http_status)->toBe(405)
        ->and($log->response_code)->toBe(405)
        ->and($log->error_message)->toBe('Invalid Alif response')
        ->and($log->response_body['http_status'] ?? null)->toBe(405)
        ->and($log->response_body['method'] ?? null)->toBe('POST')
        ->and($log->response_body['url'] ?? null)->toBe('https://test-web.alif.tj/v2/')
        ->and($log->response_body)->toHaveKey('body')
        ->and($log->response_body['body'])->toBeNull()
        ->and($log->response_body['note'] ?? null)->toBe('Empty Alif response')
        ->and($log->response_body['headers']['allow'] ?? null)->toBe('GET')
        ->and($log->request_payload['url'] ?? null)->toBe('https://test-web.alif.tj/v2/')
        ->and($log->request_payload['method'] ?? null)->toBe('POST')
        ->and($log->request_payload['body']['token'] ?? null)->toBe(SensitiveKeyRedactor::REDACTED)
        ->and($log->formattedResponseBody())->toContain('Empty Alif response')
        ->and($log->formattedCurl())->toContain("curl -i -X POST 'https://test-web.alif.tj/v2/'")
        ->and($log->formattedCurl())->toContain('--data-raw')
        ->and($log->formattedCurl())->toContain('# Response HTTP 405');
});

it('stores the full alif json error body on init failure', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 400,
            'message' => 'Invalid token',
            'details' => ['reason' => 'signature mismatch'],
        ], 400),
    ]);

    Sanctum::actingAs(alifAcquiringCustomer());

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '100.00'])
        ->assertStatus(502)
        ->assertJsonPath('message', 'Invalid token');

    $log = AlifApiLog::query()->firstOrFail();

    expect($log->is_successful)->toBeFalse()
        ->and($log->http_status)->toBe(400)
        ->and($log->response_code)->toBe(400)
        ->and($log->error_message)->toBe('Invalid token')
        ->and($log->response_body['message'] ?? null)->toBe('Invalid token')
        ->and($log->response_body['details'] ?? null)->toBe(['reason' => 'signature mismatch']);
});

it('stores the raw alif status error body on checktxn failure', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
        'https://test-web.alif.tj/checktxn' => Http::response('<html>status down</html>', 500, [
            'Content-Type' => 'text/html',
        ]),
    ]);

    $user = alifAcquiringCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '25.00'])
        ->json('data.order_id');

    $this->getJson('/api/v1/auth/wallet/alif/status/'.$orderId)
        ->assertOk();

    $log = AlifApiLog::query()->where('action', AlifAcquiringService::ACTION_CHECKTXN)->firstOrFail();

    expect($log->is_successful)->toBeFalse()
        ->and($log->http_status)->toBe(500)
        ->and($log->response_body['body'] ?? null)->toContain('status down')
        ->and($log->response_body['url'] ?? null)->toBe('https://test-web.alif.tj/checktxn');
});
