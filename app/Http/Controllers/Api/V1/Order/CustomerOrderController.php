<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Order\OrderLogisticsRequest;
use App\Http\Resources\Api\V1\Order\CustomerOrderResource;
use App\Models\CustomerOrder;
use App\Services\Order\CustomerOrderLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends ApiController
{
    public function __construct(
        protected CustomerOrderLifecycleService $lifecycleService,
    ) {}

    public function paymentPreview(Request $request, CustomerOrder $order): JsonResponse
    {
        $preview = $this->lifecycleService->paymentPreview($request->user(), $order);

        return $this->successResponse($preview, __('api.order_payment_preview_ready'));
    }

    public function pay(Request $request, CustomerOrder $order): JsonResponse
    {
        $order = $this->lifecycleService->pay($request->user(), $order);

        return $this->successResponse(
            (new CustomerOrderResource($order))->resolve(),
            __('api.order_paid')
        );
    }

    public function sync(Request $request, CustomerOrder $order): JsonResponse
    {
        $order = $this->lifecycleService->syncFromElim($request->user(), $order);

        return $this->successResponse(
            (new CustomerOrderResource($order))->resolve(),
            __('api.order_synced')
        );
    }

    public function cancel(Request $request, CustomerOrder $order): JsonResponse
    {
        $order = $this->lifecycleService->cancel($request->user(), $order);

        return $this->successResponse(
            (new CustomerOrderResource($order))->resolve(),
            __('api.order_cancelled')
        );
    }

    public function logistics(OrderLogisticsRequest $request, CustomerOrder $order): JsonResponse
    {
        $logistics = $this->lifecycleService->logistics(
            $request->user(),
            $order,
            $request->validated('package_id')
        );

        return $this->successResponse($logistics, __('api.order_logistics_fetched'));
    }

    public function elimPurchasingWallet(): JsonResponse
    {
        $wallet = $this->lifecycleService->elimPurchasingWallet();

        return $this->successResponse($wallet, __('api.elim_purchasing_wallet_fetched'));
    }

    public function elimExchangeRates(): JsonResponse
    {
        $rates = $this->lifecycleService->elimExchangeRates();

        return $this->successResponse($rates, __('api.elim_exchange_rates_fetched'));
    }
}
