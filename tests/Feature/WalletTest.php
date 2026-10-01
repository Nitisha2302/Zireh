<?php

use App\Models\Admin;
use App\Models\AlifPayment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Alif\Contracts\AlifWalletCreditorInterface;
use App\Services\Wallet\WalletService;
use App\Support\Alif\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makeWalletAdmin(): Admin
{
    return Admin::create([
        'name' => 'Wallet Admin',
        'username' => 'walletadmin',
        'email' => 'wallet@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ]);
}

it('creates wallet on first balance lookup', function () {
    $user = User::factory()->create();

    $balance = app(WalletService::class)->getBalance($user);

    expect($balance)->toBe(0.0)
        ->and($user->fresh()->wallet)->not->toBeNull()
        ->and($user->wallet->currency)->toBe('TJS');
});

it('allows admin to add funds and revert deposit', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $deposit = $service->adminAddFunds($user, 100.50, 'Test deposit', $admin);

    expect((float) $service->getBalance($user))->toBe(100.50)
        ->and($deposit->type)->toBe(WalletTransaction::TYPE_CREDIT)
        ->and($deposit->source)->toBe(WalletTransaction::SOURCE_ADMIN_DEPOSIT)
        ->and($deposit->isRevertable())->toBeTrue();

    $revert = $service->adminRevertTransaction($deposit->fresh(), $admin);

    expect((float) $service->getBalance($user))->toBe(0.0)
        ->and($revert->type)->toBe(WalletTransaction::TYPE_DEBIT)
        ->and($revert->source)->toBe(WalletTransaction::SOURCE_ADMIN_REVERT)
        ->and($deposit->fresh()->status)->toBe(WalletTransaction::STATUS_REVERTED)
        ->and($deposit->fresh()->isRevertable())->toBeFalse();
});

it('allows admin to deduct funds and create debit transaction', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $service->adminAddFunds($user, 100, 'Initial', $admin);

    $deduct = $service->adminDeductFunds($user, 30, 'Manual adjustment', $admin);

    expect((float) $service->getBalance($user))->toBe(70.0)
        ->and($deduct->type)->toBe(WalletTransaction::TYPE_DEBIT)
        ->and($deduct->source)->toBe(WalletTransaction::SOURCE_ADMIN_DEDUCT)
        ->and((float) $deduct->balance_before)->toBe(100.0)
        ->and((float) $deduct->balance_after)->toBe(70.0);
});

it('rejects deduct when balance is insufficient', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $service->adminAddFunds($user, 20, null, $admin);

    expect(fn () => $service->adminDeductFunds($user, 50, null, $admin))
        ->toThrow(ValidationException::class);
});

it('rejects revert when balance is insufficient', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $deposit = $service->adminAddFunds($user, 50, null, $admin);
    $service->adminRevertTransaction($deposit->fresh(), $admin);

    expect(fn () => $service->adminRevertTransaction($deposit->fresh(), $admin))
        ->toThrow(ValidationException::class);
});

it('no longer exposes a customer-triggered wallet deposit endpoint', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/wallet/deposit', ['amount' => 50.25])
        ->assertNotFound();

    expect(app(WalletService::class)->getBalance($user))->toBe(0.0);
});

it('filters wallet transactions via api query parameters', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $service->adminAddFunds($user, 100, 'Admin deposit', $admin);

    $payment = AlifPayment::create([
        'user_id' => $user->id,
        'order_id' => 'WU-WALLET-FILTER-1',
        'amount' => '25.00',
        'status' => AlifPayment::STATUS_PENDING,
    ]);

    DB::transaction(fn () => app(AlifWalletCreditorInterface::class)
        ->credit($user, $payment, Money::of('25.00')));

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/wallet/transactions?type=credit&source=alif_deposit')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source', WalletTransaction::SOURCE_ALIF_DEPOSIT);
});

it('returns wallet balance and transactions for authenticated customer', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);
    $service->adminAddFunds($user, 25, 'API test', $admin);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/wallet')
        ->assertOk()
        ->assertJsonPath('data.balance', 25)
        ->assertJsonPath('data.currency', 'TJS');

    $this->getJson('/api/v1/auth/wallet/transactions')
        ->assertOk()
        ->assertJsonPath('data.0.type', WalletTransaction::TYPE_CREDIT)
        ->assertJsonPath('data.0.amount', 25)
        ->assertJsonStructure([
            'data' => [
                ['id', 'type', 'source', 'amount', 'signed_amount', 'balance_after', 'status'],
            ],
            'meta' => ['pagination'],
        ]);
});

it('allows admin to view wallet transaction list page', function () {
    $admin = makeWalletAdmin();
    $user = User::factory()->create();
    app(WalletService::class)->adminAddFunds($user, 10, 'List test', $admin);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.wallet-transactions.index'))
        ->assertOk()
        ->assertSee('Wallet Transactions');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.customers.wallet', $user))
        ->assertOk()
        ->assertSee('Customer Wallet')
        ->assertSee('сом. 10.00');
});
