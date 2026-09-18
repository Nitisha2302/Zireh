<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Warehouse\NearestWarehousesRequest;
use App\Http\Resources\Api\V1\WarehouseResource;
use App\Models\Warehouse;
use App\Services\Warehouse\WarehouseService;
use Illuminate\Http\JsonResponse;

class WarehouseController extends ApiController
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}

    public function list(): JsonResponse
    {
        $result = $this->warehouseService->listActive();

        return $this->successResponse([
            'warehouses' => WarehouseResource::collection($result['warehouses'])->resolve(),
        ], __('api.warehouses_listed'));
    }

    public function index(NearestWarehousesRequest $request): JsonResponse
    {
        if ($request->filled('address_id')) {
            $result = $this->warehouseService->listNearestToAddress(
                $request->user(),
                (int) $request->validated('address_id')
            );

            return $this->successResponse([
                'origin' => $result['origin'],
                'warehouses' => WarehouseResource::collection($result['warehouses'])->resolve(),
            ], __('api.warehouses_listed'));
        }

        return $this->list();
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse = $this->warehouseService->findActive($warehouse);

        if (! $warehouse) {
            return $this->errorResponse(__('api.warehouse_not_available'), [], 404);
        }

        return $this->successResponse(
            (new WarehouseResource($warehouse))->resolve(),
            __('api.warehouse_fetched')
        );
    }
}
