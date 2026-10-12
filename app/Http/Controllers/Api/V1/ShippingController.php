<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Shipping\CalculateShippingRequest;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingMethodService;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Http\JsonResponse;

class ShippingController extends ApiController
{
    public function methods(ShippingMethodService $shippingMethodService): JsonResponse
    {
        return $this->successResponse(
            [
                'route' => [
                    'from' => ShippingMethod::ROUTE_FROM,
                    'to' => ShippingMethod::ROUTE_TO,
                ],
                'methods' => $shippingMethodService->listForApi(),
            ],
            __('api.shipping_methods_listed')
        );
    }

    public function calculate(CalculateShippingRequest $request, ShippingRateService $shippingRateService): JsonResponse
    {
        $validated = $request->validated();

        $warehouseId = isset($validated['warehouse_id'])
            ? (int) $validated['warehouse_id']
            : ($request->user()?->warehouse_id !== null ? (int) $request->user()->warehouse_id : null);

        $mode = $validated['mode'] ?? 'auto';
        $weightKg = isset($validated['weight_g'])
            ? round(((float) $validated['weight_g']) / 1000, 3)
            : (isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : null);

        return $this->successResponse(
            $shippingRateService->calculate(
                $validated['method'],
                $weightKg,
                isset($validated['length_cm']) ? (float) $validated['length_cm'] : null,
                isset($validated['width_cm']) ? (float) $validated['width_cm'] : null,
                isset($validated['height_cm']) ? (float) $validated['height_cm'] : null,
                $warehouseId,
                $mode,
            ),
            __('api.shipping_cost_calculated')
        );
    }
}
