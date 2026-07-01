<?php

namespace App\Http\Controllers\Api\V1\Cart\Platform1688;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\Platform1688\Platform1688CheckoutRequest;
use App\Http\Resources\Api\V1\Cart\Platform1688\Platform1688CheckoutPreviewResource;
use App\Http\Resources\Api\V1\Cart\Platform1688\Platform1688OrderResource;
use App\Models\CustomerOrder;
use App\Services\Cart\Platform1688\Platform1688OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Platform1688CheckoutController extends ApiController
{
    public function __construct(
        protected Platform1688OrderService $orderService,
    ) {}

    public function preview(Platform1688CheckoutRequest $request): JsonResponse
    {
        $preview = $this->orderService->preview($request->user(), $request->validated());

        return $this->successResponse(
            (new Platform1688CheckoutPreviewResource($preview))->resolve(),
            __('api.checkout_preview_ready')
        );
    }

    public function checkout(Platform1688CheckoutRequest $request): JsonResponse
    {
        $order = $this->orderService->checkout($request->user(), $request->validated());

        return $this->successResponse(
            (new Platform1688OrderResource($order))->resolve(),
            __('api.order_created'),
            201
        );
    }

    public function orders(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);
        $orders = $this->orderService->list($request->user(), max($perPage, 1));

        return $this->successResponse(
            Platform1688OrderResource::collection($orders)->resolve(),
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
        $order = $this->orderService->show($request->user(), $order);

        return $this->successResponse(
            (new Platform1688OrderResource($order))->resolve(),
            __('api.order_fetched')
        );
    }
}
