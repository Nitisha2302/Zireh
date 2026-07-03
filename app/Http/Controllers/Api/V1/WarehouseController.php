<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Warehouse\NearestWarehousesRequest;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Services\Warehouse\WarehouseService;
use Illuminate\Http\JsonResponse;

class WarehouseController extends ApiController
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}

    public function index(NearestWarehousesRequest $request): JsonResponse
    {
        $result = $this->warehouseService->listNearestToAddress(
            $request->user(),
            (int) $request->validated('address_id')
        );

        return $this->successResponse([
            'origin' => $result['origin'],
            'warehouses' => WarehouseResource::collection($result['warehouses'])->resolve(),
        ], __('api.warehouses_listed'));
    }
}
