<?php

namespace App\Services\Cart\Taobao;

use App\Exceptions\Elim\ElimException;
use App\Models\Platform;
use App\Models\User;
use App\Models\UserCartItem;
use App\Services\Elim\TaobaoService;
use App\Services\PlatformCommissionService;
use App\Support\Elim\ProductNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TaobaoCartService
{
    public function __construct(
        protected TaobaoService $taobaoService,
        protected ProductNormalizer $normalizer,
        protected PlatformCommissionService $commissionService,
    ) {}

    public function getCart(User $user): array
    {
        $items = $this->cartQuery($user)->get();

        return $this->buildCartResponse($items);
    }

    /**
     * @return array{item: UserCartItem, created: bool, cart: array}
     */
    public function add(User $user, array $data): array
    {
        $productId = (string) $data['product_id'];
        $skuId = (string) ($data['sku_id'] ?? '');
        $quantity = (int) $data['quantity'];
        $lang = $data['lang'] ?? $user->preferredLocale();

        $this->assertQuantity($quantity);

        $detail = $this->fetchProduct($productId, $lang);
        $sku = $this->resolveSku($detail, $skuId);
        $this->assertAvailable($detail, $quantity);

        $existing = $this->cartQuery($user)
            ->where('product_id', $productId)
            ->where('sku_id', $skuId)
            ->first();

        $unitPrice = $this->normalizer->resolveUnitPrice($detail, $sku);
        $snapshot = $this->normalizer->cartSnapshot($detail, $sku);

        $item = $this->cartQuery($user)->updateOrCreate(
            [
                'product_id' => $productId,
                'sku_id' => $skuId,
            ],
            [
                'platform' => UserCartItem::PLATFORM_TAOBAO,
                'marketplace_id' => (string) ($detail['marketplace_id'] ?? $productId),
                'quantity' => ($existing?->quantity ?? 0) + $quantity,
                'unit_price' => $unitPrice,
                'product_snapshot' => $snapshot,
                'selected_attributes' => $data['selected_attributes'] ?? $existing?->selected_attributes,
                'synced_at' => now(),
            ]
        );

        return [
            'item' => $item->fresh(),
            'created' => ! $existing,
            'cart' => $this->getCart($user),
        ];
    }

    public function update(User $user, UserCartItem $item, array $data): array
    {
        $this->ensureOwnership($user, $item);

        $lang = $data['lang'] ?? $user->preferredLocale();
        $detail = $this->fetchProduct($item->product_id, $lang);

        $skuId = array_key_exists('sku_id', $data)
            ? (string) $data['sku_id']
            : $item->sku_id;

        $sku = $this->resolveSku($detail, $skuId);

        $quantity = array_key_exists('quantity', $data)
            ? (int) $data['quantity']
            : $item->quantity;

        $this->assertQuantity($quantity);
        $this->assertAvailable($detail, $quantity);

        $item->update([
            'sku_id' => $skuId,
            'marketplace_id' => (string) ($detail['marketplace_id'] ?? $item->product_id),
            'quantity' => $quantity,
            'unit_price' => $this->normalizer->resolveUnitPrice($detail, $sku),
            'product_snapshot' => $this->normalizer->cartSnapshot($detail, $sku),
            'selected_attributes' => $data['selected_attributes'] ?? $item->selected_attributes,
            'synced_at' => now(),
        ]);

        return $this->getCart($user);
    }

    public function remove(User $user, UserCartItem $item): array
    {
        $this->ensureOwnership($user, $item);
        $item->delete();

        return $this->getCart($user);
    }

    public function clear(User $user): array
    {
        $this->cartQuery($user)->delete();

        return $this->buildCartResponse(collect());
    }

    public function cartItems(User $user): Collection
    {
        return $this->cartQuery($user)->get();
    }

    public function clearCartItems(User $user): void
    {
        $this->cartQuery($user)->delete();
    }

    protected function cartQuery(User $user)
    {
        return $user->cartItems()
            ->where('platform', UserCartItem::PLATFORM_TAOBAO)
            ->latest();
    }

    protected function fetchProduct(string $productId, string $lang): array
    {
        try {
            $detail = $this->taobaoService->find($productId, $lang);
        } catch (ElimException) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart_product_not_found')],
            ]);
        }

        if (empty($detail['id']) || ($detail['platform'] ?? null) !== UserCartItem::PLATFORM_TAOBAO) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart_platform_mismatch')],
            ]);
        }

        return $detail;
    }

    protected function resolveSku(array $detail, string $skuId): ?array
    {
        if (! $this->normalizer->hasSkus($detail)) {
            return null;
        }

        if ($skuId === '') {
            throw ValidationException::withMessages([
                'sku_id' => [__('api.cart_sku_required')],
            ]);
        }

        $sku = $this->normalizer->findSku($detail, $skuId);

        if (! $sku) {
            throw ValidationException::withMessages([
                'sku_id' => [__('api.cart_sku_invalid')],
            ]);
        }

        return $sku;
    }

    protected function assertQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => [__('api.cart_quantity_invalid')],
            ]);
        }
    }

    protected function assertAvailable(array $detail, int $quantity): void
    {
        $status = strtolower((string) ($detail['status'] ?? ''));

        if (in_array($status, ['off_shelf', 'unavailable', 'inactive', 'deleted'], true)) {
            throw ValidationException::withMessages([
                'product_id' => [__('api.cart_product_unavailable')],
            ]);
        }

        if (isset($detail['quantity']) && is_numeric($detail['quantity']) && (int) $detail['quantity'] < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => [__('api.cart_product_unavailable')],
            ]);
        }
    }

    protected function buildCartResponse(Collection $items): array
    {
        $subtotal = round($items->sum(fn (UserCartItem $item): float => $item->lineSubtotal()), 2);
        $commission = $this->resolveCommission($subtotal);

        return [
            'platform' => UserCartItem::PLATFORM_TAOBAO,
            'items' => $items->values()->all(),
            'summary' => [
                'item_count' => $items->count(),
                'total_quantity' => (int) $items->sum('quantity'),
                'subtotal' => $subtotal,
                'commission' => $commission,
                'total' => round($subtotal + ($commission['commission_amount'] ?? 0), 2),
            ],
        ];
    }

    protected function resolveCommission(float $subtotal): array
    {
        if ($subtotal <= 0) {
            return [
                'slab_id' => null,
                'commission_percentage' => 0.0,
                'commission_amount' => 0.0,
            ];
        }

        $platform = Platform::query()->where('code', UserCartItem::PLATFORM_TAOBAO)->first();

        if (! $platform) {
            throw ValidationException::withMessages([
                'platform' => [__('api.platform_not_available')],
            ]);
        }

        return $this->commissionService->calculate($platform, $subtotal);
    }

    protected function ensureOwnership(User $user, UserCartItem $item): void
    {
        if ($item->user_id !== $user->id || $item->platform !== UserCartItem::PLATFORM_TAOBAO) {
            throw ValidationException::withMessages([
                'cart_item' => [__('api.cart_item_not_found')],
            ]);
        }
    }
}
