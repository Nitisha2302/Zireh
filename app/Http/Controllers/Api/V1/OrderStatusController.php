<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\Order\OrderStatusService;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends ApiController
{
    public function index(OrderStatusService $orderStatusService): JsonResponse
    {
        $statuses = $orderStatusService->listActive()->map(fn ($status): array => [
            'code' => $status->code,
            'name' => $status->name,
            'color' => $status->color,
            'is_system' => $status->is_system,
            'sort_order' => $status->sort_order,
        ])->values()->all();

        return $this->successResponse(
            ['statuses' => $statuses],
            __('api.order_statuses_listed')
        );
    }
}
