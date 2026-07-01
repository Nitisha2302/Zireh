<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
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
        $user = $request->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return $this->successResponse(
            (new WalletResource($wallet))->resolve(),
            __('api.wallet_fetched')
        );
    }

    public function transactions(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);
        $transactions = $this->walletService->listForUser($request->user(), max($perPage, 1));

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
