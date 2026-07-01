<?php

use App\Models\Admin;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        ->and($user->wallet->currency)->toBe('CNY');
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

it('rejects revert when balance is insufficient', function () {
    $user = User::factory()->create();
    $admin = makeWalletAdmin();
    $service = app(WalletService::class);

    $deposit = $service->adminAddFunds($user, 50, null, $admin);
    $service->adminRevertTransaction($deposit->fresh(), $admin);

    expect(fn () => $service->adminRevertTransaction($deposit->fresh(), $admin))
        ->toThrow(Illuminate\Validation\ValidationException::class);
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
        ->assertJsonPath('data.currency', 'CNY');

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
        ->assertSee('¥10.00');
});
