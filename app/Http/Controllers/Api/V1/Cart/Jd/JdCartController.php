<?php

namespace App\Http\Controllers\Api\V1\Cart\Jd;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\Jd\StoreJdCartItemRequest;
use App\Http\Requests\Api\V1\Cart\Jd\UpdateJdCartItemRequest;
use App\Http\Resources\Api\V1\Cart\Jd\JdCartResource;
use App\Models\UserCartItem;
use App\Services\Cart\Jd\JdCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JdCartController extends ApiController
{
    public function __construct(
        protected JdCartService $cartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        return $this->successResponse(
            (new JdCartResource($cart))->resolve(),
            __('api.cart_items_listed')
        );
    }

    public function store(StoreJdCartItemRequest $request): JsonResponse
    {
        $result = $this->cartService->add($request->user(), $request->validated());

        return $this->successResponse(
            (new JdCartResource($result['cart']))->resolve(),
            __('api.cart_item_added'),
            $result['created'] ? 201 : 200
        );
    }

    public function update(UpdateJdCartItemRequest $request, UserCartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->update($request->user(), $cartItem, $request->validated());

        return $this->successResponse(
            (new JdCartResource($cart))->resolve(),
            __('api.cart_item_updated')
        );
    }

    public function destroy(Request $request, UserCartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->remove($request->user(), $cartItem);

        return $this->successResponse(
            (new JdCartResource($cart))->resolve(),
            __('api.cart_item_removed')
        );
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return $this->successResponse(
            (new JdCartResource($cart))->resolve(),
            __('api.cart_cleared')
        );
    }
}
