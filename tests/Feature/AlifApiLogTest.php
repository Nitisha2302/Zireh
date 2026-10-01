<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\AlifApiLogDetailPage;
use App\Livewire\Admin\Settings\AlifApiLogListPage;
use App\Models\Admin;
use App\Models\AlifApiLog;
use App\Models\AlifPayment;
use App\Models\User;
use App\Services\Alif\AlifAcquiringService;
use App\Services\Alif\AlifApiLogger;
use App\Support\Alif\AlifAcquiringConfig;
use App\Support\Logging\SensitiveKeyRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

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
        'alif.log_retention_days' => 30,
    ]);

    SettingHelper::clearCache();
    app(AlifAcquiringConfig::class)->clearCache();
});

function alifLogAdmin(): Admin
{
    return Admin::create([
        'name' => 'Alif Log Admin',
        'username' => 'aliflogadmin',
        'email' => 'aliflogadmin@example.com',
        'password' => Hash::make('secret-password'),
        'role' => Admin::ROLE_SUPER_ADMIN,
        'email_verified_at' => now(),
    ]);
}

function alifLogCustomer(): User
{
    return User::factory()->create([
        'name' => 'Farhod Rahimov',
        'phone' => '+992901234567',
        'status' => User::STATUS_ACTIVE,
    ]);
}

it('records a successful init request with its timing', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
    ]);

    Sanctum::actingAs(alifLogCustomer());

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '100.50'])
        ->assertOk();

    $log = AlifApiLog::query()->firstOrFail();
    $payment = AlifPayment::query()->firstOrFail();

    expect(AlifApiLog::query()->count())->toBe(1)
        ->and($log->action)->toBe(AlifAcquiringService::ACTION_INIT)
        ->and($log->payment_id)->toBe($payment->order_id)
        ->and($log->amount)->toBe('100.50')
        ->and($log->is_successful)->toBeTrue()
        ->and($log->alif_payment_id)->toBe($payment->id)
        ->and((float) $log->duration_ms)->toBeGreaterThan(0);
});

it('records a valid callback and redacts the token', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
    ]);

    Sanctum::actingAs(alifLogCustomer());
    $orderId = $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])->json('data.order_id');

    $token = app(AlifAcquiringService::class)->generateToken($orderId.'okTXN-1');

    $this->postJson('/api/v1/payments/alif/callback', [
        'orderId' => $orderId,
        'status' => 'ok',
        'transactionId' => 'TXN-1',
        'token' => $token,
        'password' => 'should-not-store',
    ])->assertOk();

    $log = AlifApiLog::query()->where('action', AlifAcquiringService::ACTION_CALLBACK)->firstOrFail();

    expect($log->is_successful)->toBeTrue()
        ->and($log->request_payload['token'] ?? null)->toBe(SensitiveKeyRedactor::REDACTED)
        ->and($log->request_payload['password'] ?? null)->toBe(SensitiveKeyRedactor::REDACTED);
});

it('still inits when logging fails', function () {
    Http::fake([
        'https://test-web.alif.tj/v2/' => Http::response([
            'code' => 200,
            'url' => 'https://pay.alif.test/checkout',
            'message' => 'ok',
        ], 200),
    ]);

    Sanctum::actingAs(alifLogCustomer());

    app()->singleton(AlifApiLogger::class, fn () => new class(new SensitiveKeyRedactor) extends AlifApiLogger
    {
        public function record(
            string $action,
            array $requestPayload,
            mixed $responseBody,
            ?int $httpStatus,
            bool $successful,
            ?float $durationMs = null,
            ?string $orderId = null,
            ?string $account = null,
            ?string $amount = null,
            ?int $responseCode = null,
            bool $authorized = true,
            ?string $ipAddress = null,
            ?string $errorMessage = null,
            ?int $alifPaymentId = null,
        ): AlifApiLog {
            throw new RuntimeException('log write failed');
        }
    });

    $this->postJson('/api/v1/auth/wallet/alif/init', ['amount' => '50.00'])
        ->assertOk();

    expect(AlifPayment::query()->count())->toBe(1)
        ->and(AlifApiLog::query()->count())->toBe(0);
});

it('shows the alif log list page to admins', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    AlifApiLog::create([
        'action' => AlifAcquiringService::ACTION_INIT,
        'payment_id' => 'WU-TEST',
        'response_code' => 200,
        'is_successful' => true,
    ]);

    Livewire::test(AlifApiLogListPage::class)
        ->assertSee(__('admin.alif_api_logs'))
        ->assertSee('WU-TEST');
});

it('filters the alif log list by action and result', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    AlifApiLog::create([
        'action' => AlifAcquiringService::ACTION_INIT,
        'payment_id' => 'PAY-OK',
        'is_successful' => true,
        'response_code' => 200,
    ]);
    AlifApiLog::create([
        'action' => AlifAcquiringService::ACTION_CALLBACK,
        'payment_id' => 'CB-FAIL',
        'is_successful' => false,
        'response_code' => 400,
    ]);

    Livewire::test(AlifApiLogListPage::class)
        ->set('actionFilter', AlifAcquiringService::ACTION_INIT)
        ->assertSee('PAY-OK')
        ->assertDontSee('CB-FAIL');
});

it('shows the alif log detail page', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    $log = AlifApiLog::create([
        'action' => AlifAcquiringService::ACTION_INIT,
        'payment_id' => 'WU-DETAIL',
        'response_code' => 200,
        'is_successful' => true,
        'request_payload' => ['order_id' => 'WU-DETAIL'],
    ]);

    Livewire::test(AlifApiLogDetailPage::class, ['log' => $log])
        ->assertSee('WU-DETAIL');
});

it('allows an admin to purge old alif logs', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    $old = AlifApiLog::create(['action' => 'init', 'payment_id' => 'OLD-1', 'response_code' => 200]);
    $old->created_at = now()->subDays(40);
    $old->save();

    AlifApiLog::create(['action' => 'init', 'payment_id' => 'RECENT-1', 'response_code' => 200]);

    Livewire::test(AlifApiLogListPage::class)->call('clearOldLogs');

    expect(AlifApiLog::query()->count())->toBe(1)
        ->and(AlifApiLog::query()->first()->payment_id)->toBe('RECENT-1');
});
