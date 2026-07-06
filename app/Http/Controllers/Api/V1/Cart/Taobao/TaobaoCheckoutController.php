<?php

namespace App\Http\Controllers\Api\V1\Cart\Taobao;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\Taobao\TaobaoCheckoutRequest;
use App\Http\Resources\Api\V1\Cart\Taobao\TaobaoCheckoutPreviewResource;
use App\Http\Resources\Api\V1\Cart\Taobao\TaobaoOrderResource;
use App\Models\CustomerOrder;
use App\Services\Cart\Taobao\TaobaoOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaobaoCheckoutController extends ApiController
{
    public function __construct(
        protected TaobaoOrderService $orderService,
    ) {}

    public function preview(TaobaoCheckoutRequest $request): JsonResponse
    {
        $preview = $this->orderService->preview($request->user(), $request->validated());

        return $this->successResponse(
            (new TaobaoCheckoutPreviewResource($preview))->resolve(),
            __('api.checkout_preview_ready')
        );
    }

    public function checkout(TaobaoCheckoutRequest $request): JsonResponse
    {
        $order = $this->orderService->checkout($request->user(), $request->validated());

        return $this->successResponse(
            (new TaobaoOrderResource($order))->resolve(),
            __('api.order_created'),
            201
        );
    }

    public function orders(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);
        $orders = $this->orderService->list($request->user(), max($perPage, 1));

        return $this->successResponse(
            TaobaoOrderResource::collection($orders)->resolve(),
            __('api.orders_listed'),
            200,
            [
                'pagination' => [
                    'total' => $orders->total(),
                    'page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage(),
                ],
            ]
        );
    }

    public function show(Request $request, CustomerOrder $order): JsonResponse
    {
        $sync = filter_var($request->query('sync', false), FILTER_VALIDATE_BOOLEAN);
        $order = $this->orderService->show($request->user(), $order, $sync);

        return $this->successResponse(
            (new TaobaoOrderResource($order))->resolve(),
            __('api.order_fetched')
        );
    }
}
