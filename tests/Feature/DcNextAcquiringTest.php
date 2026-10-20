<?php

use App\Helpers\SettingHelper;
use App\Models\DcNextPayment;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\DcNext\DcNextInvoiceService;
use App\Services\Wallet\WalletService;
use App\Support\DcNext\DcNextConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.url' => 'https://app.test',
        'dc_next.merchant' => 'merchant-test-id',
        'dc_next.secret' => 'secret-test-key',
        'dc_next.callback_url' => 'https://app.test/api/v1/webhooks/dc-next/invoice',
        'dc_next.create_url' => 'https://invoice.dc.tj/v1/create',
        'dc_next.check_url' => 'https://invoice.dc.tj/v1/check',
        'dc_next.default_currency' => 'TJS',
        'dc_next.invoice_exp_minutes' => 40,
        'dc_next.min_amount' => '1.00',
        'dc_next.max_amount' => '20000.00',
    ]);

    SettingHelper::clearCache();
    app(DcNextConfig::class)->clearCache();
});

function dcNextCustomer(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'phone' => '+992901234567',
        'status' => User::STATUS_ACTIVE,
    ], $overrides));
}

function fakeDcNextInit(?array $json = null): void
{
    Http::fake([
        'https://invoice.dc.tj/v1/create' => Http::response($json ?? [
            'status' => 'success',
            'invoice_id' => '26549494',
            'invoice_url' => 'https://pay.dc.tj/?a=26549494',
            'exp_time' => 40,
        ], 200),
        'https://invoice.dc.tj/v1/check' => Http::response([
            'status' => 'paid',
            'payid' => 'PAY-CHECK-1',
            'message' => '<g>Счёт оплачен</g>',
        ], 200),
    ]);
}

function dcNextCallbackSign(string $orderId): string
{
    $config = app(DcNextConfig::class);

    return DcNextInvoiceService::signCreateOrCheck(
        $config->merchant(),
        $orderId,
        $config->secret(),
    );
}

it('inits a dc next invoice in diram and stores a pending payment without crediting wallet', function () {
    fakeDcNextInit();
    $user = dcNextCustomer(['phone' => '901234567']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '50.25'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount', '50.25')
        ->assertJsonPath('data.invoice_id', '26549494')
        ->assertJsonPath('data.payment_url', 'https://pay.dc.tj/?a=26549494');

    $payment = DcNextPayment::query()->firstOrFail();

    expect($payment->user_id)->toBe($user->id)
        ->and($payment->status)->toBe(DcNextPayment::STATUS_PENDING)
        ->and($payment->amount_tjs)->toBe('50.25')
        ->and($payment->invoice_id)->toBe('26549494')
        ->and(app(WalletService::class)->getBalance($user))->toBe(0.0);

    Http::assertSent(function (HttpClientRequest $request) use ($payment): bool {
        return $request->url() === 'https://invoice.dc.tj/v1/create'
            && $request['amount'] === 5025
            && $request['phone'] === '992901234567'
            && $request['sign'] === DcNextInvoiceService::signCreateOrCheck('merchant-test-id', $payment->order_id, 'secret-test-key');
    });
});

it('rejects init when the customer has no phone', function () {
    fakeDcNextInit();
    Sanctum::actingAs(dcNextCustomer(['phone' => '']));

    $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '50.00'])
        ->assertStatus(422);

    expect(DcNextPayment::query()->count())->toBe(0);
    Http::assertNothingSent();
});

it('rejects init when dc next is not configured', function () {
    fakeDcNextInit();
    config([
        'dc_next.merchant' => '',
        'dc_next.secret' => '',
    ]);
    app(DcNextConfig::class)->clearCache();

    Sanctum::actingAs(dcNextCustomer());

    $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '50.00'])
        ->assertStatus(500);

    expect(DcNextPayment::query()->count())->toBe(0);
    Http::assertNothingSent();
});

it('rejects init amounts outside the configured range', function () {
    fakeDcNextInit();
    Sanctum::actingAs(dcNextCustomer());

    $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '0.50'])
        ->assertStatus(422);

    expect(DcNextPayment::query()->count())->toBe(0);
    Http::assertNothingSent();
});

it('credits the wallet once from a valid dc next callback and ignores replays', function () {
    fakeDcNextInit();
    $user = dcNextCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '50.00'])
        ->json('data.order_id');

    $payload = [
        'merchant' => 'merchant-test-id',
        'order_id' => $orderId,
        'invoice_id' => '26549494',
        'payid' => 'PAY-99',
        'sign' => dcNextCallbackSign($orderId),
    ];

    $this->postJson('/api/v1/webhooks/dc-next/invoice', $payload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->postJson('/api/v1/webhooks/dc-next/invoice', $payload)
        ->assertOk()
        ->assertJsonPath('success', true);

    $payment = DcNextPayment::query()->firstOrFail();

    expect($payment->status)->toBe(DcNextPayment::STATUS_PAID)
        ->and($payment->payid)->toBe('PAY-99')
        ->and($payment->wallet_transaction_id)->not->toBeNull()
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_DC_NEXT_DEPOSIT)->count())->toBe(1)
        ->and(app(WalletService::class)->getBalance($user->fresh()))->toBe(50.0);
});

it('rejects a callback with an invalid sign and leaves payment unpaid', function () {
    fakeDcNextInit();
    $user = dcNextCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '50.00'])
        ->json('data.order_id');

    $this->postJson('/api/v1/webhooks/dc-next/invoice', [
        'merchant' => 'merchant-test-id',
        'order_id' => $orderId,
        'invoice_id' => '26549494',
        'payid' => 'PAY-99',
        'sign' => 'invalid-md5-signature',
    ])->assertStatus(401)->assertJsonPath('success', false);

    expect(DcNextPayment::query()->first()->status)->toBe(DcNextPayment::STATUS_PENDING)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

it('credits from status poll when dc next check reports paid', function () {
    Http::fake([
        'https://invoice.dc.tj/v1/create' => Http::response([
            'status' => 'success',
            'invoice_id' => '26549494',
            'invoice_url' => 'https://pay.dc.tj/?a=26549494',
        ], 200),
        'https://invoice.dc.tj/v1/check' => Http::response([
            'status' => 'pending',
            'payid' => 'PAY-STATUS-1',
            'message' => '<g>Счёт оплачен</g>',
        ], 200),
    ]);

    $user = dcNextCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '25.00'])
        ->json('data.order_id');

    $this->getJson('/api/v1/auth/wallet/dc-next/status/'.$orderId)
        ->assertOk()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath('data.transaction_id', 'PAY-STATUS-1');

    expect(app(WalletService::class)->getBalance($user->fresh()))->toBe(25.0)
        ->and(WalletTransaction::query()->where('source', WalletTransaction::SOURCE_DC_NEXT_DEPOSIT)->count())->toBe(1);
});

it('verifies callbacks with admin-saved merchant credentials instead of env', function () {
    fakeDcNextInit();
    Setting::query()->create(['key' => DcNextConfig::SETTING_MERCHANT, 'value' => 'admin-merchant']);
    Setting::query()->create(['key' => DcNextConfig::SETTING_SECRET, 'value' => 'admin-secret']);
    SettingHelper::clearCache();
    app(DcNextConfig::class)->clearCache();

    $user = dcNextCustomer();
    Sanctum::actingAs($user);

    $orderId = $this->postJson('/api/v1/auth/wallet/dc-next/init', ['amount' => '10.00'])
        ->json('data.order_id');

    $envSign = DcNextInvoiceService::signCreateOrCheck('merchant-test-id', $orderId, 'secret-test-key');

    $this->postJson('/api/v1/webhooks/dc-next/invoice', [
        'order_id' => $orderId,
        'payid' => 'PAY-ADMIN',
        'sign' => $envSign,
    ])->assertStatus(401);

    $this->postJson('/api/v1/webhooks/dc-next/invoice', [
        'order_id' => $orderId,
        'payid' => 'PAY-ADMIN',
        'sign' => dcNextCallbackSign($orderId),
    ])->assertOk()->assertJsonPath('success', true);

    expect(DcNextPayment::query()->first()->isPaid())->toBeTrue();
});
