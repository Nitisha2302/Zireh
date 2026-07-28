<?php

namespace App\Http\Controllers\Api\V1\Cart\Jd;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\Jd\JdCheckoutRequest;
use App\Http\Resources\Api\V1\Cart\Jd\JdCheckoutPreviewResource;
use App\Http\Resources\Api\V1\Cart\Jd\JdOrderResource;
use App\Models\CustomerOrder;
use App\Services\Cart\Jd\JdOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JdCheckoutController extends ApiController
{
    public function __construct(
        protected JdOrderService $orderService,
    ) {}

    public function preview(JdCheckoutRequest $request): JsonResponse
    {
        $preview = $this->orderService->preview($request->user(), $request->validated());

        return $this->successResponse(
            (new JdCheckoutPreviewResource($preview))->resolve(),
            __('api.checkout_preview_ready')
        );
    }

    public function checkout(JdCheckoutRequest $request): JsonResponse
    {
        $order = $this->orderService->checkout($request->user(), $request->validated());

        return $this->successResponse(
            (new JdOrderResource($order))->resolve(),
            __('api.order_created'),
            201
        );
    }

    public function orders(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);
        $orders = $this->orderService->list($request->user(), max($perPage, 1));

        return $this->successResponse(
            JdOrderResource::collection($orders)->resolve(),
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
            (new JdOrderResource($order))->resolve(),
            __('api.order_fetched')
        );
    }
}
