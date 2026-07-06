<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Shipping\CalculateShippingRequest;
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
                    'from' => \App\Models\ShippingMethod::ROUTE_FROM,
                    'to' => \App\Models\ShippingMethod::ROUTE_TO,
                ],
                'methods' => $shippingMethodService->listForApi(),
            ],
            __('api.shipping_methods_listed')
        );
    }

    public function calculate(CalculateShippingRequest $request, ShippingRateService $shippingRateService): JsonResponse
    {
        $validated = $request->validated();

        return $this->successResponse(
            $shippingRateService->calculate(
                $validated['method'],
                (float) $validated['weight_kg'],
                isset($validated['length_cm']) ? (float) $validated['length_cm'] : null,
                isset($validated['width_cm']) ? (float) $validated['width_cm'] : null,
                isset($validated['height_cm']) ? (float) $validated['height_cm'] : null,
            ),
            __('api.shipping_cost_calculated')
        );
    }
}
