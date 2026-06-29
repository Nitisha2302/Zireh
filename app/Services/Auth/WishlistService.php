<?php

namespace App\Services\Auth;

use App\Exceptions\Elim\ElimException;
use App\Models\User;
use App\Models\UserWishlistItem;
use App\Services\Elim\Alibaba1688Service;
use App\Services\Elim\Contracts\MarketplaceProductService;
use App\Services\Elim\TaobaoService;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    public function __construct(
        protected TaobaoService $taobaoService,
        protected Alibaba1688Service $alibaba1688Service,
        protected ProductNormalizer $normalizer,
    ) {}

    /**
     * @return array{item: UserWishlistItem, created: bool}
     */
    public function add(User $user, array $data): array
    {
        $platform = $data['platform'];
        $productId = (string) $data['product_id'];
        $lang = $data['lang'] ?? $user->preferredLocale();

        $existing = $user->wishlists()
            ->where('platform', $platform)
            ->where('product_id', $productId)
            ->first();

        try {
            $detail = $this->resolveProductService($platform)->find($productId, $lang);
        } catch (ElimException) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.wishlist_product_not_found')],
            ]);
        }

        if (empty($detail['id'])) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.wishlist_product_not_found')],
            ]);
        }

        $snapshot = $this->normalizer->wishlistSnapshot($detail);

        $item = $user->wishlists()->updateOrCreate(
            [
                'platform' => $platform,
                'product_id' => $productId,
            ],
            [
                'product_snapshot' => $snapshot,
                'synced_at' => now(),
            ]
        );

        return [
            'item' => $item->fresh(),
            'created' => ! $existing,
        ];
    }

    public function list(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->wishlists()
            ->latest()
            ->paginate($perPage);
    }

    public function delete(User $user, UserWishlistItem $item): void
    {
        $this->ensureOwnership($user, $item);

        $item->delete();
    }

    protected function resolveProductService(string $platform): MarketplaceProductService
    {
        return match ($platform) {
            UserWishlistItem::PLATFORM_TAOBAO => $this->taobaoService,
            UserWishlistItem::PLATFORM_1688 => $this->alibaba1688Service,
            default => throw ValidationException::withMessages([
                'platform' => [__('api.wishlist_invalid_platform')],
            ]),
        };
    }

    protected function ensureOwnership(User $user, UserWishlistItem $item): void
    {
        if ($item->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'wishlist' => [__('api.wishlist_item_not_found')],
            ]);
        }
    }
}
