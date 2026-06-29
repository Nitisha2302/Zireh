<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\StoreWishlistItemRequest;
use App\Http\Resources\Api\V1\Auth\WishlistItemResource;
use App\Models\UserWishlistItem;
use App\Services\Auth\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends ApiController
{
    public function __construct(
        protected WishlistService $wishlistService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 50);
        $perPage = max($perPage, 1);

        $items = $this->wishlistService->list($request->user(), $perPage);

        return $this->successResponse(
            WishlistItemResource::collection($items)->resolve(),
            __('api.wishlist_items_listed'),
            200,
            [
                'pagination' => [
                    'total' => $items->total(),
                    'page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'last_page' => $items->lastPage(),
                ],
            ]
        );
    }

    public function store(StoreWishlistItemRequest $request): JsonResponse
    {
        $result = $this->wishlistService->add($request->user(), $request->validated());

        return $this->successResponse(
            (new WishlistItemResource($result['item']))->resolve(),
            __('api.wishlist_item_added'),
            $result['created'] ? 201 : 200
        );
    }

    public function destroy(Request $request, UserWishlistItem $wishlist): JsonResponse
    {
        $this->wishlistService->delete($request->user(), $wishlist);

        return $this->successResponse(null, __('api.wishlist_item_removed'));
    }
}
