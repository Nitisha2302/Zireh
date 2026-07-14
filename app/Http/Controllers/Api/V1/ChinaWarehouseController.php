<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Support\Elim\ElimWarehouseAddress;
use Illuminate\Http\JsonResponse;

class ChinaWarehouseController extends ApiController
{
    public function show(): JsonResponse
    {
        if (! ElimWarehouseAddress::isConfigured()) {
            return $this->errorResponse(
                __('api.elim_warehouse_address_missing'),
                [],
                404
            );
        }

        $address = ElimWarehouseAddress::get();

        return $this->successResponse([
            'name' => (string) ($address['name'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
            'mobile' => (string) ($address['mobile'] ?? ''),
            'address' => (string) ($address['address'] ?? ''),
            'province' => (string) ($address['province'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'area' => (string) ($address['area'] ?? ''),
        ], __('api.china_warehouse_fetched'));
    }
}
