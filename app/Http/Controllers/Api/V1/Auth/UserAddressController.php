<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\StoreUserAddressRequest;
use App\Http\Requests\Api\V1\Auth\UpdateUserAddressRequest;
use App\Http\Resources\Api\V1\Auth\UserAddressResource;
use App\Models\UserAddress;
use App\Services\Auth\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends ApiController
{
    public function __construct(
        protected UserAddressService $userAddressService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->userAddressService->list($request->user());

        return $this->successResponse(
            UserAddressResource::collection($addresses)->resolve(),
            __('api.addresses_listed')
        );
    }

    public function store(StoreUserAddressRequest $request): JsonResponse
    {
        $address = $this->userAddressService->create($request->user(), $request->validated());

        return $this->successResponse(
            (new UserAddressResource($address))->resolve(),
            __('api.address_created'),
            201
        );
    }

    public function update(UpdateUserAddressRequest $request, UserAddress $address): JsonResponse
    {
        $address = $this->userAddressService->update($request->user(), $address, $request->validated());

        return $this->successResponse(
            (new UserAddressResource($address))->resolve(),
            __('api.address_updated')
        );
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        $this->userAddressService->delete($request->user(), $address);

        return $this->successResponse(null, __('api.address_deleted'));
    }

    public function setDefault(Request $request, UserAddress $address): JsonResponse
    {
        $address = $this->userAddressService->setDefault($request->user(), $address);

        return $this->successResponse(
            (new UserAddressResource($address))->resolve(),
            __('api.default_address_updated')
        );
    }
}
