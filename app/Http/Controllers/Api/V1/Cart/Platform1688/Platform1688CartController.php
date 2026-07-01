<?php

namespace App\Http\Controllers\Api\V1\Cart\Platform1688;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Cart\Platform1688\StorePlatform1688CartItemRequest;
use App\Http\Requests\Api\V1\Cart\Platform1688\UpdatePlatform1688CartItemRequest;
use App\Http\Resources\Api\V1\Cart\Platform1688\Platform1688CartResource;
use App\Models\UserCartItem;
use App\Services\Cart\Platform1688\Platform1688CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Platform1688CartController extends ApiController
{
    public function __construct(
        protected Platform1688CartService $cartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        return $this->successResponse(
            (new Platform1688CartResource($cart))->resolve(),
            __('api.cart_items_listed')
        );
    }

    public function store(StorePlatform1688CartItemRequest $request): JsonResponse
    {
        $result = $this->cartService->add($request->user(), $request->validated());

        return $this->successResponse(
            (new Platform1688CartResource($result['cart']))->resolve(),
            __('api.cart_item_added'),
            $result['created'] ? 201 : 200
        );
    }

    public function update(UpdatePlatform1688CartItemRequest $request, UserCartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->update($request->user(), $cartItem, $request->validated());

        return $this->successResponse(
            (new Platform1688CartResource($cart))->resolve(),
            __('api.cart_item_updated')
        );
    }

    public function destroy(Request $request, UserCartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->remove($request->user(), $cartItem);

        return $this->successResponse(
            (new Platform1688CartResource($cart))->resolve(),
            __('api.cart_item_removed')
        );
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return $this->successResponse(
            (new Platform1688CartResource($cart))->resolve(),
            __('api.cart_cleared')
        );
    }
}
