<?php

use App\Helpers\SettingHelper;
use App\Models\AlifPayment;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\Alif\AlifPaymentRepository;
use App\Services\Alif\Contracts\AlifPaymentRepositoryInterface;
use App\Services\Alif\Contracts\AlifWalletCreditorInterface;
use App\Services\Wallet\WalletService;
use App\Support\Alif\AlifProviderConfig;
use App\Support\Alif\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'alif.login' => 'alif-demo',
        'alif.password' => 'alif-secret',
        'alif.min_amount' => '1.00',
        'alif.max_amount' => '20000.00',
        'alif.currency' => 'TJS',
        'alif.srv_id' => null,
    ]);

    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();
});

function alifAuthHeaders(string $login = 'alif-demo', string $password = 'alif-secret'): array
{
    return ['Authorization' => base64_encode($login.':'.$password)];
}

function alifCustomer(string $phone = '+992901234567'): User
{
    return User::factory()->create([
        'name' => 'Farhod Rahimov',
        'phone' => $phone,
        'status' => User::STATUS_ACTIVE,
    ]);
}

function alifRequest(array $payload, ?array $headers = null)
{
    return test()->postJson('/api/alif', $payload, $headers ?? alifAuthHeaders());
}

function alifPayPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 12345132564875,
        'action' => 'pay',
        'account' => '992901234567',
        'amount' => 100.50,
        'time' => '2026-01-02T15:04:05Z',
    ], $overrides);
}

/**
 * A payment that another request already settled and committed.
 */
function alifSettledPayment(User $user, string $paymentId, string $amount): AlifPayment
{
    $payment = AlifPayment::create([
        'payment_id' => $paymentId,
        'account' => (string) $user->phone,
        'user_id' => $user->id,
        'amount' => $amount,
        'status' => AlifPayment::STATUS_PENDING,
    ]);

    DB::transaction(function () use ($user, $payment, $amount) {
        $transaction = app(AlifWalletCreditorInterface::class)
            ->credit($user, $payment, Money::of($amount));

        app(AlifPaymentRepository::class)->markPaid($payment, $transaction);
    });

    return $payment->fresh();
}

/**
 * Reproduces the concurrency window: the pre-transaction duplicate check misses
 * the winner because it had not committed yet, so the insert is what discovers
 * the collision.
 */
function alifBlindFirstLookup(): void
{
    app()->singleton(AlifPaymentRepositoryInterface::class, function ($app) {
        return new class($app->make(AlifPaymentRepository::class)) implements AlifPaymentRepositoryInterface
        {
            private bool $firstLookup = true;

            public function __construct(private readonly AlifPaymentRepository $inner) {}

            public function findByPaymentId(string $paymentId): ?AlifPayment
            {
                if ($this->firstLookup) {
                    $this->firstLookup = false;

                    return null;
                }

                return $this->inner->findByPaymentId($paymentId);
            }

            public function lockByPaymentId(string $paymentId): ?AlifPayment
            {
                return $this->inner->lockByPaymentId($paymentId);
            }

            public function createPending(array $attributes): AlifPayment
            {
                return $this->inner->createPending($attributes);
            }

            public function markPaid(AlifPayment $payment, WalletTransaction $transaction): AlifPayment
            {
                return $this->inner->markPaid($payment, $transaction);
            }

            public function markRejected(AlifPayment $payment, int $code): AlifPayment
            {
                return $this->inner->markRejected($payment, $code);
            }
        };
    });
}

it('confirms the account for every phone format alif might send', function (string $account) {
    alifCustomer('+992901234567');

    alifRequest(['id' => 500001, 'action' => 'check', 'account' => $account])
        ->assertOk()
        ->assertJsonPath('code', 302)
        ->assertJsonPath('id', 500001);
})->with([
    '+992901234567',
    '992901234567',
    '901234567',
    '+992 90 123 45 67',
]);

it('returns the balance to show the customer on check', function () {
    $user = alifCustomer();
    app(WalletService::class)->getOrCreateWallet($user);

    $response = alifRequest(['id' => 500002, 'action' => 'check', 'account' => '992901234567'])
        ->assertOk()
        ->assertJsonPath('code', 302);

    expect($response->json('info_for_client'))
        ->toContain('Farhod')
        ->toContain('0.00');
});

it('rejects a check for an account nobody owns', function () {
    alifCustomer();

    alifRequest(['id' => 500003, 'action' => 'check', 'account' => '992555555555'])
        ->assertOk()
        ->assertJsonPath('code', 404);
});

it('rejects a check for a blocked customer', function () {
    alifCustomer()->update(['status' => User::STATUS_BLOCKED]);

    alifRequest(['id' => 500004, 'action' => 'check', 'account' => '992901234567'])
        ->assertOk()
        ->assertJsonPath('code', 404);
});

it('credits the wallet for a valid pay request', function () {
    $user = alifCustomer();

    $response = alifRequest(alifPayPayload())
        ->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('id', 12345132564875)
        ->assertJsonStructure(['code', 'id', 'response_id']);

    $payment = AlifPayment::query()->firstOrFail();
    $transaction = WalletTransaction::query()->firstOrFail();

    expect($payment->status)->toBe(AlifPayment::STATUS_PAID)
        ->and($payment->payment_id)->toBe('12345132564875')
        ->and($payment->amount)->toBe('100.50')
        ->and($payment->response_id)->toBe((string) $transaction->id)
        ->and($payment->wallet_transaction_id)->toBe($transaction->id)
        ->and($payment->user_id)->toBe($user->id)
        ->and($response->json('response_id'))->toBe((string) $transaction->id)
        ->and($transaction->type)->toBe(WalletTransaction::TYPE_CREDIT)
        ->and($transaction->source)->toBe(WalletTransaction::SOURCE_ALIF_DEPOSIT)
        ->and($transaction->amount)->toBe('100.50')
        ->and($transaction->balance_before)->toBe('0.00')
        ->and($transaction->balance_after)->toBe('100.50')
        ->and($transaction->reference_type)->toBe(AlifPayment::class)
        ->and($transaction->reference_id)->toBe($payment->id)
        ->and($user->fresh()->wallet->balance)->toBe('100.50');
});

it('adds to an existing balance without float drift', function () {
    config(['alif.min_amount' => '0.01']);
    app(AlifProviderConfig::class)->clearCache();
    $user = alifCustomer();

    alifRequest(alifPayPayload(['id' => 600001, 'amount' => 0.10]))->assertJsonPath('code', 200);
    alifRequest(alifPayPayload(['id' => 600002, 'amount' => 0.20]))->assertJsonPath('code', 200);
    alifRequest(alifPayPayload(['id' => 600003, 'amount' => 0.07]))->assertJsonPath('code', 200);

    expect($user->fresh()->wallet->balance)->toBe('0.37');
});

it('never credits the same alif payment twice', function () {
    $user = alifCustomer();
    $payload = alifPayPayload();

    $responseId = alifRequest($payload)
        ->assertJsonPath('code', 200)
        ->json('response_id');

    alifRequest($payload)
        ->assertOk()
        ->assertJsonPath('code', 108)
        ->assertJsonPath('response_id', $responseId);

    expect(AlifPayment::query()->count())->toBe(1)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->fresh()->wallet->balance)->toBe('100.50');
});

it('credits once when a concurrent request wins the race for the same payment id', function () {
    $user = alifCustomer();
    $winner = alifSettledPayment($user, '700001', '75.25');

    alifBlindFirstLookup();

    alifRequest(alifPayPayload(['id' => 700001, 'amount' => 75.25]))
        ->assertOk()
        ->assertJsonPath('code', 108)
        ->assertJsonPath('response_id', $winner->response_id);

    expect(AlifPayment::query()->count())->toBe(1)
        ->and(WalletTransaction::query()->count())->toBe(1)
        ->and($user->fresh()->wallet->balance)->toBe('75.25');
});

it('enforces the unique payment id at the database level', function () {
    $user = alifCustomer();

    AlifPayment::create([
        'payment_id' => '800001',
        'account' => '992901234567',
        'user_id' => $user->id,
        'amount' => '10.00',
    ]);

    expect(fn () => AlifPayment::create([
        'payment_id' => '800001',
        'account' => '992901234567',
        'user_id' => $user->id,
        'amount' => '10.00',
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('refuses to pay an account nobody owns and writes nothing', function () {
    alifCustomer();

    alifRequest(alifPayPayload(['account' => '992555555555']))
        ->assertOk()
        ->assertJsonPath('code', 404);

    expect(AlifPayment::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

it('rejects amounts outside the accepted range', function (float $amount) {
    alifCustomer();

    alifRequest(alifPayPayload(['amount' => $amount]))
        ->assertOk()
        ->assertJsonPath('code', 405);

    expect(AlifPayment::query()->count())->toBe(0);
})->with([
    'below the minimum' => 0.5,
    'above the maximum' => 20000.01,
]);

it('rolls the whole payment back when the ledger write fails', function () {
    $user = alifCustomer();

    app()->singleton(AlifWalletCreditorInterface::class, fn () => new class implements AlifWalletCreditorInterface
    {
        public function credit(User $user, AlifPayment $payment, Money $amount): WalletTransaction
        {
            throw new RuntimeException('Ledger write failed.');
        }
    });

    alifRequest(alifPayPayload())
        ->assertOk()
        ->assertJsonPath('code', 520);

    expect(AlifPayment::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0)
        ->and(app(WalletService::class)->getBalance($user->fresh()))->toBe(0.0);
});

it('rejects requests that fail authorization', function (array $headers) {
    alifCustomer();

    alifRequest(alifPayPayload(), $headers)
        ->assertOk()
        ->assertJsonPath('code', 401)
        ->assertJsonPath('id', 12345132564875);

    expect(AlifPayment::query()->count())->toBe(0)
        ->and(WalletTransaction::query()->count())->toBe(0);
})->with([
    'missing header' => [[]],
    'not base64 of our credentials' => [['Authorization' => 'not-our-secret']],
    'wrong password' => [['Authorization' => 'Basic '.base64_encode('alif-demo:wrong')]],
    'wrong login' => [['Authorization' => base64_encode('someone-else:alif-secret')]],
]);

it('accepts the documented bare base64 header and a Basic prefixed one', function (string $header) {
    alifCustomer();

    alifRequest(['id' => 900001, 'action' => 'check', 'account' => '992901234567'], ['Authorization' => $header])
        ->assertOk()
        ->assertJsonPath('code', 302);
})->with([
    'bare base64' => base64_encode('alif-demo:alif-secret'),
    'basic prefixed' => 'Basic '.base64_encode('alif-demo:alif-secret'),
]);

it('refuses every request while credentials are unconfigured', function () {
    config(['alif.login' => null, 'alif.password' => null]);
    app(AlifProviderConfig::class)->clearCache();
    alifCustomer();

    alifRequest(alifPayPayload())
        ->assertOk()
        ->assertJsonPath('code', 401);
});

it('authenticates with admin-saved credentials instead of env', function () {
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_LOGIN, 'value' => 'portal-login']);
    Setting::query()->create(['key' => AlifProviderConfig::SETTING_PASSWORD, 'value' => 'portal-secret']);
    SettingHelper::clearCache();
    app(AlifProviderConfig::class)->clearCache();

    alifCustomer();

    alifRequest(['id' => 900100, 'action' => 'check', 'account' => '992901234567'])
        ->assertOk()
        ->assertJsonPath('code', 401);

    alifRequest(
        ['id' => 900101, 'action' => 'check', 'account' => '992901234567'],
        alifAuthHeaders('portal-login', 'portal-secret')
    )
        ->assertOk()
        ->assertJsonPath('code', 302);
});

it('answers a malformed request with code 400', function (array $payload) {
    alifCustomer();

    alifRequest($payload)
        ->assertOk()
        ->assertJsonPath('code', 400);

    expect(AlifPayment::query()->count())->toBe(0);
})->with([
    'missing action' => [['id' => 1000001, 'account' => '992901234567']],
    'unknown action' => [['id' => 1000002, 'action' => 'refund', 'account' => '992901234567']],
    'missing id' => [['action' => 'pay', 'account' => '992901234567', 'amount' => 10]],
    'missing account' => [['id' => 1000003, 'action' => 'pay', 'amount' => 10]],
    'missing amount' => [['id' => 1000004, 'action' => 'pay', 'account' => '992901234567']],
    'amount is not a number' => [['id' => 1000005, 'action' => 'pay', 'account' => '992901234567', 'amount' => 'a lot']],
    'amount has too many decimals' => [['id' => 1000006, 'action' => 'pay', 'account' => '992901234567', 'amount' => '10.123']],
    'negative amount' => [['id' => 1000007, 'action' => 'pay', 'account' => '992901234567', 'amount' => -10]],
]);

it('rejects a pay request carrying an unexpected srv_id', function () {
    config(['alif.srv_id' => 'wallet-topup']);
    app(AlifProviderConfig::class)->clearCache();
    alifCustomer();

    alifRequest(alifPayPayload(['srv_id' => 'something-else']))
        ->assertOk()
        ->assertJsonPath('code', 400);

    expect(AlifPayment::query()->count())->toBe(0);
});

it('stores the configured srv_id alongside the payment', function () {
    config(['alif.srv_id' => 'wallet-topup']);
    app(AlifProviderConfig::class)->clearCache();
    alifCustomer();

    alifRequest(alifPayPayload(['srv_id' => 'wallet-topup', 'is_commercial' => true]))
        ->assertJsonPath('code', 200);

    $payment = AlifPayment::query()->firstOrFail();

    expect($payment->srv_id)->toBe('wallet-topup')
        ->and($payment->is_commercial)->toBeTrue();
});

it('reports status 104 for a payment id it has never settled', function () {
    alifCustomer();

    alifRequest(['id' => 1100001, 'action' => 'status'])
        ->assertOk()
        ->assertJsonPath('code', 104);
});

it('reports status 104 for an id that was only checked', function () {
    alifCustomer();

    alifRequest(['id' => 1100002, 'action' => 'check', 'account' => '992901234567'])
        ->assertJsonPath('code', 302);

    alifRequest(['id' => 1100002, 'action' => 'status'])
        ->assertOk()
        ->assertJsonPath('code', 104);
});

it('reports status 200 with the original response id for a settled payment', function () {
    alifCustomer();

    $responseId = alifRequest(alifPayPayload(['id' => 1100003]))
        ->assertJsonPath('code', 200)
        ->json('response_id');

    alifRequest(['id' => 1100003, 'action' => 'status'])
        ->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('response_id', $responseId);
});
