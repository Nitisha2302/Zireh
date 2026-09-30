<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\AlifApiLogDetailPage;
use App\Livewire\Admin\Settings\AlifApiLogListPage;
use App\Models\Admin;
use App\Models\AlifApiLog;
use App\Models\AlifPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Alif\AlifApiLogger;
use App\Services\Wallet\WalletService;
use App\Support\Alif\AlifProviderConfig;
use App\Support\Alif\AlifResult;
use App\Support\Logging\SensitiveKeyRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'alif.login' => 'alif-demo',
        'alif.password' => 'alif-secret',
        'alif.min_amount' => '1.00',
        'alif.max_amount' => '20000.00',
        'alif.log_retention_days' => 30,
    ]);

    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();
});

function alifLogAdmin(): Admin
{
    return Admin::create([
        'name' => 'Alif Log Admin',
        'username' => 'aliflogadmin',
        'email' => 'aliflogadmin@example.com',
        'password' => Hash::make('secret-password'),
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

function alifLogHeaders(): array
{
    return ['Authorization' => base64_encode('alif-demo:alif-secret')];
}

function alifLogRequest(array $payload, ?array $headers = null)
{
    return test()->postJson('/api/alif', $payload, $headers ?? alifLogHeaders());
}

it('records a successful pay request with its timing', function () {
    alifLogCustomer();

    alifLogRequest([
        'id' => 12345132564875,
        'action' => 'pay',
        'account' => '992901234567',
        'amount' => 100.50,
    ])->assertJsonPath('code', 200);

    $log = AlifApiLog::query()->firstOrFail();
    $payment = AlifPayment::query()->firstOrFail();

    expect(AlifApiLog::query()->count())->toBe(1)
        ->and($log->action)->toBe('pay')
        ->and($log->payment_id)->toBe('12345132564875')
        ->and($log->account)->toBe('992901234567')
        ->and($log->amount)->toBe('100.50')
        ->and($log->response_code)->toBe(200)
        ->and($log->http_status)->toBe(200)
        ->and($log->authorized)->toBeTrue()
        ->and($log->is_successful)->toBeTrue()
        ->and($log->duration_ms)->not->toBeNull()
        ->and($log->alif_payment_id)->toBe($payment->id)
        ->and($log->request_payload)->toMatchArray(['action' => 'pay', 'amount' => 100.50])
        ->and($log->response_body)->toMatchArray(['code' => 200])
        ->and($log->formattedDuration())->toEndWith(' ms');
});

it('records a check request as successful only on code 302', function () {
    alifLogCustomer();

    alifLogRequest(['id' => 1, 'action' => 'check', 'account' => '992901234567'])
        ->assertJsonPath('code', 302);
    alifLogRequest(['id' => 2, 'action' => 'check', 'account' => '992555555555'])
        ->assertJsonPath('code', 404);

    expect(AlifApiLog::query()->where('payment_id', '1')->value('is_successful'))->toBeTrue()
        ->and(AlifApiLog::query()->where('payment_id', '2')->value('is_successful'))->toBeFalse();
});

it('records rejected credentials without storing the header', function () {
    alifLogCustomer();

    alifLogRequest(
        ['id' => 555, 'action' => 'pay', 'account' => '992901234567', 'amount' => 10],
        ['Authorization' => base64_encode('alif-demo:wrong')]
    )->assertJsonPath('code', 401);

    $log = AlifApiLog::query()->firstOrFail();

    expect($log->response_code)->toBe(401)
        ->and($log->authorized)->toBeFalse()
        ->and($log->is_successful)->toBeFalse()
        ->and($log->alif_payment_id)->toBeNull()
        ->and(json_encode($log->getAttributes()))
        ->not->toContain(base64_encode('alif-demo:wrong'))
        ->not->toContain('alif-secret');
});

it('records a malformed request with code 400', function () {
    alifLogCustomer();

    alifLogRequest(['id' => 777, 'action' => 'refund'])
        ->assertJsonPath('code', 400);

    $log = AlifApiLog::query()->firstOrFail();

    expect($log->response_code)->toBe(400)
        ->and($log->action)->toBe('refund')
        ->and($log->authorized)->toBeTrue()
        ->and($log->error_message)->not->toBeNull();
});

it('redacts credential-like keys inside the alif body', function () {
    alifLogCustomer();

    alifLogRequest([
        'id' => 888,
        'action' => 'pay',
        'account' => '992901234567',
        'amount' => 10,
        'info' => ['token' => 'super-secret', 'fieldId1' => 'value1'],
    ])->assertJsonPath('code', 200);

    $log = AlifApiLog::query()->firstOrFail();
    $payment = AlifPayment::query()->firstOrFail();

    expect($log->request_payload['info']['token'])->toBe(SensitiveKeyRedactor::REDACTED)
        ->and($log->request_payload['info']['fieldId1'])->toBe('value1')
        ->and($payment->request_payload['info']['token'])->toBe(SensitiveKeyRedactor::REDACTED)
        ->and(json_encode($log->getAttributes()))->not->toContain('super-secret');
});

it('keeps the retry history for one payment visible', function () {
    alifLogCustomer();

    $payload = ['id' => 999, 'action' => 'pay', 'account' => '992901234567', 'amount' => 25];

    alifLogRequest($payload)->assertJsonPath('code', 200);
    alifLogRequest($payload)->assertJsonPath('code', 108);

    $payment = AlifPayment::query()->firstOrFail();

    expect(AlifPayment::query()->count())->toBe(1)
        ->and(AlifApiLog::query()->count())->toBe(2)
        ->and($payment->logs()->pluck('response_code')->all())->toBe([200, 108]);
});

it('credits the wallet even when the request log cannot be written', function () {
    $user = alifLogCustomer();

    app()->singleton(AlifApiLogger::class, fn () => new class(new SensitiveKeyRedactor) extends AlifApiLogger
    {
        public function log(
            array $payload,
            ?AlifResult $result = null,
            mixed $responseBody = null,
            ?int $httpStatus = null,
            bool $authorized = false,
            ?float $durationMs = null,
            ?string $ipAddress = null,
            ?string $errorMessage = null,
        ): AlifApiLog {
            throw new RuntimeException('Log storage unavailable.');
        }
    });

    alifLogRequest([
        'id' => 1234,
        'action' => 'pay',
        'account' => '992901234567',
        'amount' => 40,
    ])->assertOk()->assertJsonPath('code', 200);

    expect(AlifApiLog::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->fresh()->wallet->balance)->toBe('40.00')
        ->and(app(WalletService::class)->getBalance($user->fresh()))->toBe(40.0);
});

it('shows the alif log list page to admins', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    AlifApiLog::create([
        'action' => 'pay',
        'payment_id' => '12345132564875',
        'account' => '992901234567',
        'amount' => '100.50',
        'response_code' => 200,
        'http_status' => 200,
        'authorized' => true,
        'is_successful' => true,
        'duration_ms' => '13.802',
        'ip_address' => '10.0.0.1',
        'request_payload' => ['action' => 'pay'],
        'response_body' => ['code' => 200],
    ]);

    Livewire::test(AlifApiLogListPage::class)
        ->assertSee(__('admin.alif_api_logs'))
        ->assertSee('12345132564875')
        ->assertSee('13.802 ms');
});

it('filters the alif log list by action and result', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    AlifApiLog::create([
        'action' => 'pay',
        'payment_id' => 'PAY-OK',
        'response_code' => 200,
        'is_successful' => true,
        'authorized' => true,
    ]);

    AlifApiLog::create([
        'action' => 'check',
        'payment_id' => 'CHECK-MISS',
        'response_code' => 404,
        'is_successful' => false,
        'authorized' => true,
    ]);

    Livewire::test(AlifApiLogListPage::class)
        ->set('actionFilter', 'pay')
        ->assertSee('PAY-OK')
        ->assertDontSee('CHECK-MISS')
        ->set('actionFilter', '')
        ->set('successFilter', '0')
        ->assertSee('CHECK-MISS')
        ->assertDontSee('PAY-OK')
        ->set('successFilter', '')
        ->set('search', 'CHECK-MISS')
        ->assertSee('CHECK-MISS')
        ->assertDontSee('PAY-OK');
});

it('shows the alif log detail page', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    $log = AlifApiLog::create([
        'action' => 'pay',
        'payment_id' => '12345132564875',
        'account' => '992901234567',
        'amount' => '100.50',
        'response_code' => 200,
        'http_status' => 200,
        'authorized' => true,
        'is_successful' => true,
        'duration_ms' => '0.421',
        'ip_address' => '10.0.0.1',
        'request_payload' => ['action' => 'pay', 'amount' => 100.5],
        'response_body' => ['code' => 200, 'response_id' => '17'],
    ]);

    Livewire::test(AlifApiLogDetailPage::class, ['log' => $log])
        ->assertSee('12345132564875')
        ->assertSee('0.421 ms')
        ->assertSee('10.0.0.1')
        ->assertSee('Successful');
});

it('allows an admin to purge old alif logs', function () {
    $this->actingAs(alifLogAdmin(), 'admin');

    $old = AlifApiLog::create(['action' => 'pay', 'payment_id' => 'OLD-1', 'response_code' => 200]);
    $old->forceFill([
        'created_at' => now()->subDays(40),
        'updated_at' => now()->subDays(40),
    ])->saveQuietly();

    AlifApiLog::create(['action' => 'pay', 'payment_id' => 'RECENT-1', 'response_code' => 200]);

    Livewire::test(AlifApiLogListPage::class)->call('clearOldLogs');

    expect(AlifApiLog::query()->count())->toBe(1)
        ->and(AlifApiLog::query()->first()->payment_id)->toBe('RECENT-1');
});
