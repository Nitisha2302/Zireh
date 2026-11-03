<?php

namespace App\Support\Elim;

use App\Support\Currency\CurrencyPriceConverter;

class ProductNormalizer
{
    public function __construct(
        protected CurrencyPriceConverter $currencyPriceConverter,
    ) {}

    public function listResponse(array $response, string $platform): array
    {
        $payload = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return [
            'platform' => $platform,
            'message' => $response['message'] ?? $payload['message'] ?? null,
            'pagination' => [
                'total' => $payload['paginate']['total'] ?? $payload['total'] ?? null,
                'page' => $payload['paginate']['current'] ?? $payload['page'] ?? null,
                'size' => $payload['paginate']['size'] ?? $payload['size'] ?? null,
            ],
            'items' => collect($payload['items'] ?? [])
                ->map(fn (array $item): array => $this->currencyPriceConverter->applyToProductListItem(
                    $this->listItem($item, $platform)
                ))
                ->values()
                ->all(),
            'raw' => $response,
            'currency' => $this->currencyPriceConverter->meta(),
        ];
    }

    public function detailResponse(array $response, string $platform): array
    {
        $payload = is_array($response['data'] ?? null) ? $response['data'] : $response;

        return $this->currencyPriceConverter->applyToProductDetail([
            'platform' => $platform,
            'id' => (string) ($payload['id'] ?? ''),
            'marketplace_id' => $payload['mp_id'] ?? null,
            'mi_id' => $payload['mi_id'] ?? null,
            'quote_type' => $payload['quote_type'] ?? null,
            'title' => $payload['title_en'] ?? $payload['titleEn'] ?? $payload['title'] ?? null,
            'original_title' => $payload['title'] ?? null,
            'description' => $payload['description_en'] ?? $payload['description'] ?? null,
            'url' => $payload['link'] ?? $payload['original_product_url'] ?? $payload['origin_product_link'] ?? null,
            'price' => $this->money($payload['price'] ?? null),
            'promotion_price' => $this->money($payload['promotion_price'] ?? null),
            'coupon_price' => $this->money($payload['coupon_price'] ?? null),
            'price_range' => $payload['price_range'] ?? [],
            'sku_price_ranges' => $payload['sku_price_ranges'] ?? [],
            'quantity' => $payload['quantity'] ?? null,
            'minimum_order_quantity' => $payload['moq'] ?? null,
            'unit' => $payload['unit_en'] ?? $payload['unit'] ?? null,
            'category' => [
                'id' => $payload['category_id'] ?? null,
                'name' => $payload['category_name'] ?? null,
                'path' => $payload['category_path'] ?? null,
            ],
            'shop' => [
                'id' => $payload['shop_id'] ?? null,
                'name' => $payload['shop_name'] ?? null,
                'seller_nick' => $payload['seller_nick'] ?? null,
                'seller_type' => $payload['seller_type'] ?? null,
                'level' => $payload['level'] ?? null,
            ],
            'images' => $payload['img_urls'] ?? $payload['images'] ?? $payload['detail_images'] ?? [],
            'property_images' => $payload['property_images'] ?? [],
            'videos' => $payload['video_urls'] ?? [],
            'sold' => $payload['sold'] ?? null,
            'status' => $payload['status'] ?? null,
            'skus' => $payload['skus'] ?? [],
            'attributes' => $payload['attributes'] ?? [],
            'shipping_info' => $payload['shipping_info'] ?? [],
            'fenxiao_sale_info' => $payload['fenxiao_sale_info'] ?? null,
            'seller_mix_setting' => $payload['seller_mix_setting'] ?? null,
            'extra_info' => $payload['extra_info'] ?? [],
            'raw' => $response,
        ]);
    }

    public function wishlistSnapshot(array $detail): array
    {
        $images = $detail['images'] ?? [];
        $shop = $detail['shop'] ?? [];

        return [
            'platform' => $detail['platform'] ?? null,
            'id' => (string) ($detail['id'] ?? ''),
            'mi_id' => $detail['mi_id'] ?? null,
            'title' => $detail['title'] ?? null,
            'original_title' => $detail['original_title'] ?? null,
            'url' => $detail['url'] ?? null,
            'image' => $images[0] ?? null,
            'price' => $detail['price'] ?? null,
            'promotion_price' => $detail['promotion_price'] ?? null,
            'unit' => $detail['unit'] ?? null,
            'shop' => [
                'id' => $shop['id'] ?? null,
                'name' => $shop['name'] ?? null,
            ],
        ];
    }

    public function cartSnapshot(array $detail, ?array $sku = null, int $quantity = 1): array
    {
        $snapshot = $this->wishlistSnapshot($detail);
        $snapshot['marketplace_id'] = $detail['marketplace_id'] ?? null;
        $snapshot['unit_price'] = $this->resolveUnitPrice($detail, $sku, $quantity);

        if ($sku) {
            $snapshot['sku'] = [
                'id' => (string) ($sku['mp_skuid'] ?? $sku['mp_sku_id'] ?? $sku['id'] ?? $sku['sku_id'] ?? $sku['skuId'] ?? $sku['spec_id'] ?? ''),
                'sku_id' => (string) ($sku['id'] ?? $sku['sku_id'] ?? $sku['skuId'] ?? ''),
                'spec_id' => (string) ($sku['spec_id'] ?? ''),
                'mp_sku_id' => (string) ($sku['mp_sku_id'] ?? $sku['mp_skuid'] ?? ''),
                'properties' => $sku['properties'] ?? $sku['props'] ?? $sku['options'] ?? null,
            ];
        }

        return $snapshot;
    }

    public function findSku(array $detail, string $skuId): ?array
    {
        foreach ($detail['skus'] ?? [] as $sku) {
            if (! is_array($sku)) {
                continue;
            }

            $candidates = array_filter([
                (string) ($sku['id'] ?? ''),
                (string) ($sku['sku_id'] ?? ''),
                (string) ($sku['skuId'] ?? ''),
                (string) ($sku['spec_id'] ?? ''),
                (string) ($sku['mp_skuid'] ?? ''),
                (string) ($sku['mp_sku_id'] ?? ''),
            ], fn (string $value): bool => $value !== '');

            if (in_array($skuId, $candidates, true)) {
                return $sku;
            }
        }

        return null;
    }

    public function resolveUnitPrice(array $detail, ?array $sku = null, int $quantity = 1): float
    {
        $quoteType = $detail['quote_type'] ?? null;

        if ($quoteType === 'by_volume' && ! empty($detail['price_range']) && is_array($detail['price_range'])) {
            $tierPrice = $this->resolveVolumeTierPrice($detail['price_range'], $quantity);
            if ($tierPrice !== null) {
                return $tierPrice;
            }
        }

        if ($sku) {
            $price = $this->money(
                $sku['coupon_price']
                ?? $sku['promotion_price']
                ?? $sku['dropship_price']
                ?? $sku['price']
                ?? null
            );

            if ($price !== null) {
                return $price;
            }
        }

        if ($quantity > 1 && ! empty($detail['price_range']) && is_array($detail['price_range'])) {
            $tierPrice = $this->resolveVolumeTierPrice($detail['price_range'], $quantity);
            if ($tierPrice !== null) {
                return $tierPrice;
            }
        }

        return $this->money(
            $detail['coupon_price']
            ?? $detail['promotion_price']
            ?? $detail['price']
            ?? null
        ) ?? 0.0;
    }

    public function toElimLineItem(
        string $marketplaceId,
        string $productId,
        string $skuId,
        int $quantity,
        float $unitPrice,
        ?string $platform = null,
        ?array $productSnapshot = null,
    ): array {
        $productRef = (string) ($productId !== '' ? $productId : $marketplaceId);
        $skuRef = (string) ($skuId !== '' ? $skuId : $productId);

        if (in_array($platform, ['1688', 'alibaba'], true)) {
            $specId = (string) ($productSnapshot['sku']['spec_id'] ?? $productSnapshot['spec_id'] ?? '');
            if ($specId !== '') {
                $skuRef = $specId;
            }
        } elseif ($platform === 'taobao') {
            $taobaoSkuId = (string) ($productSnapshot['sku']['sku_id'] ?? $productSnapshot['sku_id'] ?? '');
            if ($taobaoSkuId !== '') {
                $skuRef = $taobaoSkuId;
            }
        }

        $lineItem = [
            'product_ref' => $productRef,
            'sku_ref' => $skuRef,
            'mp_id' => (string) ($marketplaceId ?: $productId),
            'id' => (string) ($skuId ?: $productId),
            'quantity' => $quantity,
        ];

        if ($unitPrice > 0) {
            $lineItem['price'] = round($unitPrice, 2);
        }

        return $lineItem;
    }

    public function hasSkus(array $detail): bool
    {
        return count($detail['skus'] ?? []) > 0;
    }

    private function resolveVolumeTierPrice(array $priceRange, int $quantity): ?float
    {
        $matchedPrice = null;
        $highestMoq = -1;

        foreach ($priceRange as $tier) {
            if (! is_array($tier)) {
                continue;
            }

            $moq = (int) (
                $tier['moq']
                ?? $tier['begin_amount']
                ?? $tier['start_quantity']
                ?? $tier['min_quantity']
                ?? 1
            );
            $price = $this->money($tier['promotion_price'] ?? $tier['price'] ?? null);

            if ($price !== null && $quantity >= $moq && $moq >= $highestMoq) {
                $highestMoq = $moq;
                $matchedPrice = $price;
            }
        }

        return $matchedPrice;
    }

    private function listItem(array $item, string $platform): array
    {
        return [
            'platform' => $platform,
            'id' => (string) ($item['id'] ?? ''),
            'mi_id' => $item['mi_id'] ?? null,
            'quote_type' => $item['quote_type'] ?? null,
            'title' => $item['title_en'] ?? $item['titleEn'] ?? $item['title'] ?? null,
            'original_title' => $item['title'] ?? null,
            'url' => $item['link'] ?? $item['original_product_url'] ?? $item['origin_product_link'] ?? null,
            'image' => $item['img_url'] ?? ($item['img_urls'][0] ?? null),
            'price' => $this->money($item['price'] ?? null),
            'promotion_price' => $this->money($item['promotion_price'] ?? null),
            'coupon_price' => $this->money($item['coupon_price'] ?? null),
            'retail_price' => $this->money($item['retail_price'] ?? null),
            'wholesale_price' => $this->money($item['whosesale_price'] ?? $item['wholesale_price'] ?? null),
            'dropship_price' => $this->money($item['dropship_price'] ?? null),
            'unit' => $item['unit'] ?? null,
            'minimum_order_quantity' => $item['moq'] ?? null,
            'sales_volume' => $item['sales_volume'] ?? null,
            'retention_rate' => $item['retention_rate'] ?? null,
            'seller_type' => $item['seller_type'] ?? null,
            'shop_name' => $item['seller_name'] ?? $item['shop_name'] ?? null,
            'level' => $item['level'] ?? null,
        ];
    }

    private function money(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
