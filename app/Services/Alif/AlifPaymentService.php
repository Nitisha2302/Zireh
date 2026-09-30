<?php

namespace App\Services\Alif;

use App\Models\AlifPayment;
use App\Models\User;
use App\Services\Alif\Contracts\AlifAccountResolverInterface;
use App\Services\Alif\Contracts\AlifPaymentRepositoryInterface;
use App\Services\Alif\Contracts\AlifPaymentServiceInterface;
use App\Services\Alif\Contracts\AlifWalletCreditorInterface;
use App\Support\Alif\AlifProviderConfig;
use App\Support\Alif\AlifResponseCode;
use App\Support\Alif\AlifResult;
use App\Support\Alif\Money;
use App\Support\Currency\WalletCurrency;
use App\Support\Logging\SensitiveKeyRedactor;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Implements the Alif provider protocol.
 *
 * @see https://alifcapital.github.io/providers
 */
class AlifPaymentService implements AlifPaymentServiceInterface
{
    public const ACTION_CHECK = 'check';

    public const ACTION_PAY = 'pay';

    public const ACTION_STATUS = 'status';

    private const PAYMENT_ID_PATTERN = '/^[A-Za-z0-9_.:\-]{1,64}$/';

    private const INFO_FOR_CLIENT_LIMIT = 1000;

    public function __construct(
        private readonly AlifAccountResolverInterface $accounts,
        private readonly AlifPaymentRepositoryInterface $payments,
        private readonly AlifWalletCreditorInterface $creditor,
        private readonly SensitiveKeyRedactor $redactor,
        private readonly AlifProviderConfig $config,
    ) {}

    public function handle(array $payload): AlifResult
    {
        $id = $payload['id'] ?? null;
        $action = $this->action($payload);

        try {
            return match ($action) {
                self::ACTION_CHECK => $this->check($payload),
                self::ACTION_PAY => $this->pay($payload),
                self::ACTION_STATUS => $this->status($payload),
                default => AlifResult::failure(
                    AlifResponseCode::BAD_REQUEST,
                    $id,
                    'Missing or unsupported action.',
                ),
            };
        } catch (Throwable $exception) {
            // 520 is documented non-fatal, so Alif retries. The unique index on
            // payment_id makes that retry safe.
            $this->logFailure($action, $id, $exception);

            return AlifResult::failure(
                AlifResponseCode::UNKNOWN_ERROR,
                $id,
                $exception->getMessage(),
            );
        }
    }

    /**
     * Confirm the account exists and can receive a top-up. Read-only.
     */
    protected function check(array $payload): AlifResult
    {
        $id = $payload['id'] ?? null;

        if ($this->paymentId($id) === null) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid payment id.');
        }

        $account = $this->account($payload);

        if ($account === null) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid account.');
        }

        if (! $this->serviceIdMatches($payload)) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Unexpected srv_id.');
        }

        $user = $this->accounts->resolve($account);

        if (! $user) {
            return AlifResult::failure(AlifResponseCode::ACCOUNT_NOT_FOUND, $id, 'No active customer matches the account.');
        }

        return new AlifResult(
            code: AlifResponseCode::ACCOUNT_FOUND,
            id: $id,
            infoForClient: $this->infoForClient($user),
        );
    }

    /**
     * Credit the wallet for a verified payment, exactly once per Alif payment id.
     */
    protected function pay(array $payload): AlifResult
    {
        $id = $payload['id'] ?? null;
        $paymentId = $this->paymentId($id);

        if ($paymentId === null) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid payment id.');
        }

        // Replay before anything else: a repeat must return the earlier result
        // even if configuration has changed since.
        $existing = $this->payments->findByPaymentId($paymentId);

        if ($existing) {
            return $this->resultForExisting($existing, $id);
        }

        $account = $this->account($payload);

        if ($account === null) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid account.');
        }

        if (! $this->serviceIdMatches($payload)) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Unexpected srv_id.');
        }

        $amount = Money::tryParse($payload['amount'] ?? null);

        if (! $amount || ! $amount->isPositive()) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid amount.');
        }

        if (! $amount->isWithin($this->minAmount(), $this->maxAmount())) {
            return AlifResult::failure(
                AlifResponseCode::AMOUNT_OUT_OF_RANGE,
                $id,
                'Amount outside the accepted range.',
            );
        }

        $user = $this->accounts->resolve($account);

        if (! $user) {
            return AlifResult::failure(AlifResponseCode::ACCOUNT_NOT_FOUND, $id, 'No active customer matches the account.');
        }

        try {
            return DB::transaction(
                fn (): AlifResult => $this->settle($paymentId, $account, $user, $amount, $payload)
            );
        } catch (UniqueConstraintViolationException) {
            // A concurrent request inserted the same payment id first. The
            // transaction rolled back, so nothing was credited twice; read the
            // winner's committed row and echo its result.
            return $this->resultForConcurrentDuplicate($paymentId, $id);
        }
    }

    /**
     * Report whether a payment settled. Read-only.
     */
    protected function status(array $payload): AlifResult
    {
        $id = $payload['id'] ?? null;
        $paymentId = $this->paymentId($id);

        if ($paymentId === null) {
            return AlifResult::failure(AlifResponseCode::BAD_REQUEST, $id, 'Missing or invalid payment id.');
        }

        $payment = $this->payments->findByPaymentId($paymentId);

        if (! $payment) {
            return AlifResult::failure(AlifResponseCode::TRANSACTION_NOT_FOUND, $id, 'Unknown payment id.');
        }

        if ($payment->isPaid()) {
            return new AlifResult(
                code: AlifResponseCode::SUCCESS,
                id: $id,
                responseId: $payment->response_id,
                alifPaymentId: $payment->id,
            );
        }

        if ($payment->isRejected()) {
            return AlifResult::failure(AlifResponseCode::REJECTED, $id, alifPaymentId: $payment->id);
        }

        return AlifResult::failure(AlifResponseCode::PROCESSING, $id, alifPaymentId: $payment->id);
    }

    /**
     * Runs inside one transaction. The payment row is inserted first so the
     * unique index on payment_id — not application logic — is what serializes
     * concurrent requests for the same Alif payment.
     */
    protected function settle(
        string $paymentId,
        string $account,
        User $user,
        Money $amount,
        array $payload,
    ): AlifResult {
        $payment = $this->payments->createPending([
            'payment_id' => $paymentId,
            'account' => $account,
            'user_id' => $user->id,
            'amount' => $amount->value(),
            'currency' => $this->currency(),
            'srv_id' => $this->serviceId($payload),
            'is_commercial' => (bool) ($payload['is_commercial'] ?? false),
            'request_payload' => $this->redactor->redact($payload),
        ]);

        $transaction = $this->creditor->credit($user, $payment, $amount);
        $this->payments->markPaid($payment, $transaction);

        return new AlifResult(
            code: AlifResponseCode::SUCCESS,
            id: $payload['id'] ?? null,
            responseId: (string) $transaction->id,
            alifPaymentId: $payment->id,
        );
    }

    protected function resultForConcurrentDuplicate(string $paymentId, mixed $id): AlifResult
    {
        $winner = $this->payments->findByPaymentId($paymentId);

        if (! $winner) {
            // The other request rolled back after all. Non-fatal so Alif retries.
            return AlifResult::failure(
                AlifResponseCode::TRANSACTION_EXISTS,
                $id,
                'Concurrent request for the same payment id.',
            );
        }

        return $this->resultForExisting($winner, $id);
    }

    protected function resultForExisting(AlifPayment $payment, mixed $id): AlifResult
    {
        if ($payment->isPaid()) {
            return new AlifResult(
                code: AlifResponseCode::DUPLICATE_SUCCESS,
                id: $id,
                responseId: $payment->response_id,
                alifPaymentId: $payment->id,
            );
        }

        if ($payment->isRejected()) {
            return AlifResult::failure(AlifResponseCode::REJECTED, $id, alifPaymentId: $payment->id);
        }

        return AlifResult::failure(AlifResponseCode::PROCESSING, $id, alifPaymentId: $payment->id);
    }

    protected function action(array $payload): ?string
    {
        $action = $payload['action'] ?? null;

        return is_string($action) ? strtolower(trim($action)) : null;
    }

    /**
     * Alif sends `id` as a JSON number; echo it back untouched but store a
     * predictable string.
     */
    protected function paymentId(mixed $id): ?string
    {
        if (! is_string($id) && ! is_int($id)) {
            return null;
        }

        $value = trim((string) $id);

        return preg_match(self::PAYMENT_ID_PATTERN, $value) === 1 ? $value : null;
    }

    protected function account(array $payload): ?string
    {
        $account = $payload['account'] ?? null;

        if (! is_string($account) && ! is_int($account)) {
            return null;
        }

        $value = trim((string) $account);

        return $value !== '' && mb_strlen($value) <= 64 ? $value : null;
    }

    protected function serviceId(array $payload): ?string
    {
        $srvId = $payload['srv_id'] ?? null;

        if (! is_string($srvId) && ! is_int($srvId)) {
            return null;
        }

        $value = trim((string) $srvId);

        return $value !== '' ? Str::limit($value, 64, '') : null;
    }

    /**
     * Only an explicit mismatch is rejected. srv_id is optional in the
     * protocol, so its absence is always acceptable.
     */
    protected function serviceIdMatches(array $payload): bool
    {
        $expected = $this->config->srvId();

        if ($expected === '') {
            return true;
        }

        $provided = $this->serviceId($payload);

        return $provided === null || $provided === $expected;
    }

    protected function infoForClient(User $user): string
    {
        $balance = Money::tryParse($user->wallet?->balance) ?? Money::zero();
        $currency = $user->wallet?->currency ?? $this->currency();
        $name = Str::before(trim((string) $user->name), ' ');

        $lines = array_filter([
            $name !== '' ? 'Клиент: '.Str::limit($name, 40, '') : null,
            'Баланс: '.$balance->value().' '.WalletCurrency::symbol($currency),
        ]);

        return Str::limit(implode("\n", $lines), self::INFO_FOR_CLIENT_LIMIT, '');
    }

    protected function currency(): string
    {
        return $this->config->currency();
    }

    protected function minAmount(): Money
    {
        return $this->config->minAmount();
    }

    protected function maxAmount(): Money
    {
        return $this->config->maxAmount();
    }

    protected function logFailure(?string $action, mixed $id, Throwable $exception): void
    {
        // Deliberately narrow: no request body, no credentials, no account.
        Log::channel((string) config('alif.log_channel', 'alif'))->error('Alif provider request failed.', [
            'action' => $action,
            'payment_id' => is_scalar($id) ? (string) $id : null,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
