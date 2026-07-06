<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\DepositWalletFundsRequest;
use App\Http\Requests\Api\V1\Auth\ListWalletTransactionsRequest;
use App\Http\Resources\Api\V1\Auth\WalletResource;
use App\Http\Resources\Api\V1\Auth\WalletTransactionResource;
use App\Services\Wallet\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends ApiController
{
    public function __construct(
        protected WalletService $walletService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return $this->successResponse(
            (new WalletResource($wallet))->resolve(),
            __('api.wallet_fetched')
        );
    }

    public function deposit(DepositWalletFundsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $transaction = $this->walletService->depositFunds(
            $user,
            (float) $validated['amount'],
            $validated['description'] ?? null,
            $validated['payment_reference'] ?? null,
        );

        $wallet = $this->walletService->getOrCreateWallet($user);

        return $this->successResponse(
            [
                'wallet' => (new WalletResource($wallet))->resolve(),
                'transaction' => (new WalletTransactionResource($transaction))->resolve(),
            ],
            __('api.wallet_deposit_successful'),
            201
        );
    }

    public function transactions(ListWalletTransactionsRequest $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $transactions = $this->walletService->listForUser(
            $request->user(),
            $request->filters(),
            max($perPage, 1)
        );

        return $this->successResponse(
            WalletTransactionResource::collection($transactions)->resolve(),
            __('api.wallet_transactions_listed'),
            200,
            [
                'pagination' => [
                    'total' => $transactions->total(),
                    'page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'last_page' => $transactions->lastPage(),
                ],
            ]
        );
    }
}
