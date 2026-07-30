<?php

namespace App\Support\RapidApi;

/**
 * Maps RapidAPI JD envelopes into ELIM-shaped arrays for ProductNormalizer.
 */
class JdToElimMapper
{
    /**
     * @param  array<string, mixed>  $envelope
     * @return array<string, mixed>
     */
    public function toSearchResponse(array $envelope): array
    {
        $items = collect($envelope['data'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $this->toListItem($item))
            ->values()
            ->all();

        $page = (int) ($envelope['data'][0]['_page'] ?? 1);
        $total = (int) ($envelope['data'][0]['_totalCount'] ?? count($items));
        $size = count($items);

        if ($size === 0 && isset($envelope['count'])) {
            $size = (int) $envelope['count'];
        }

        return [
            'message' => null,
            'paginate' => [
                'total' => $total,
                'current' => $page > 0 ? $page : 1,
                'size' => $size > 0 ? $size : 20,
            ],
            'items' => $items,
        ];
    }

    /**
     * Build a detail-shaped JD array from price + optional exact search match + comments.
     * The new JD RapidAPI has no product-detail endpoint.
     *
     * @param  array<string, mixed>  $price
     * @param  array<string, mixed>|null  $searchItem
     * @param  array<string, mixed>  $commentsEnvelope
     * @return array<string, mixed>
     */
    public function buildDetailFromPriceSearchComments(
        array $price,
        ?array $searchItem,
        array $commentsEnvelope = [],
    ): array {
        $itemId = (string) ($price['itemId'] ?? $searchItem['itemId'] ?? '');

        $comments = collect($commentsEnvelope['data'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item));

        $color = $searchItem['color'] ?? null;
        $size = $searchItem['size'] ?? null;

        if (($color === null || $color === '') || ($size === null || $size === '')) {
            $firstComment = $comments->first();
            if (is_array($firstComment)) {
                $color = $color ?: ($firstComment['reviewProductColor'] ?? null);
                $size = $size ?: ($firstComment['reviewProductSize'] ?? null);
            }
        }

        $imgUrls = [];
        $coverUrl = $searchItem['coverUrl'] ?? null;
        if (is_string($coverUrl) && $coverUrl !== '') {
            $imgUrls[] = $coverUrl;
        }

        foreach ($comments as $comment) {
            $photos = $comment['reviewPhotos'] ?? [];
            if (! is_array($photos)) {
                continue;
            }
            foreach ($photos as $photo) {
                if (is_string($photo) && $photo !== '' && ! in_array($photo, $imgUrls, true)) {
                    $imgUrls[] = $photo;
                }
            }
        }

        $skuPairs = [];
        foreach ($comments as $comment) {
            $c = $comment['reviewProductColor'] ?? null;
            $s = $comment['reviewProductSize'] ?? null;
            if (($c === null || $c === '') && ($s === null || $s === '')) {
                continue;
            }
            $key = (string) $c.'|'.(string) $s;
            $skuPairs[$key] = ['color' => $c, 'size' => $s];
        }

        $detail = [
            'itemId' => $itemId,
            'productTitle' => $searchItem['productTitle'] ?? null,
            'coverUrl' => $imgUrls[0] ?? null,
            'imgUrls' => $imgUrls,
            'price' => $price['price'] ?? ($searchItem['price'] ?? null),
            'currency' => $price['currency'] ?? ($searchItem['currency'] ?? 'CNY'),
            'itemUrl' => $price['itemUrl'] ?? ($searchItem['itemUrl'] ?? null),
            'shopId' => $searchItem['shopId'] ?? null,
            'shopName' => $searchItem['shopName'] ?? null,
            'shopUrl' => $searchItem['shopUrl'] ?? null,
            'sellerType' => $searchItem['sellerType'] ?? null,
            'isJdSelf' => $searchItem['isJdSelf'] ?? null,
            'categoryIds' => $searchItem['categoryIds'] ?? null,
            'color' => $color,
            'size' => $size,
            'skuPairs' => array_values($skuPairs),
            'status' => $price['status'] ?? ($searchItem['status'] ?? 'success'),
        ];

        return $this->toDetailResponse($detail, $price);
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @param  string  $itemId
     * @return array<string, mixed>|null
     */
    public function findExactSearchItem(array $envelope, string $itemId): ?array
    {
        foreach ($envelope['data'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ((string) ($item['itemId'] ?? '') === $itemId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>|null  $price
     * @return array<string, mixed>
     */
    public function toDetailResponse(array $detail, ?array $price = null): array
    {
        $itemId = (string) ($detail['itemId'] ?? '');
        $resolvedPrice = $this->resolvePrice($detail, $price);
        $coverUrl = $detail['coverUrl'] ?? null;
        $color = $detail['color'] ?? null;
        $size = $detail['size'] ?? null;
        $categoryIds = is_array($detail['categoryIds'] ?? null) ? $detail['categoryIds'] : [];
        $categoryId = $detail['categoryId'] ?? (count($categoryIds) ? end($categoryIds) : null);

        $attributes = [];
        foreach ([
            'color' => $color,
            'size' => $size,
            'weight' => $detail['weight'] ?? null,
            'width' => $detail['width'] ?? null,
            'height' => $detail['height'] ?? null,
            'length' => $detail['length'] ?? null,
        ] as $name => $value) {
            if ($value !== null && $value !== '') {
                $attributes[] = ['name' => $name, 'value' => $value];
            }
        }

        $imgUrls = [];
        if (is_array($detail['imgUrls'] ?? null)) {
            foreach ($detail['imgUrls'] as $url) {
                if (is_string($url) && $url !== '') {
                    $imgUrls[] = $url;
                }
            }
        }
        if ($imgUrls === [] && is_string($coverUrl) && $coverUrl !== '') {
            $imgUrls = [$coverUrl];
        }

        $skuPairs = is_array($detail['skuPairs'] ?? null) ? $detail['skuPairs'] : [];
        $skus = [];

        if ($skuPairs !== []) {
            foreach ($skuPairs as $index => $pair) {
                if (! is_array($pair)) {
                    continue;
                }
                $pairColor = $pair['color'] ?? null;
                $pairSize = $pair['size'] ?? null;
                $properties = array_filter([
                    'color' => $pairColor,
                    'size' => $pairSize,
                ], fn (mixed $value): bool => $value !== null && $value !== '');

                $skus[] = [
                    'id' => $itemId.':'.($index + 1),
                    'sku_id' => $itemId.':'.($index + 1),
                    'price' => $resolvedPrice,
                    'promotion_price' => $resolvedPrice,
                    'quantity' => $this->numericOrNull($detail['stockQuantity'] ?? null),
                    'properties' => $properties !== [] ? $properties : null,
                ];
            }
        }

        if ($skus === []) {
            $properties = array_filter([
                'color' => $color,
                'size' => $size,
            ], fn (mixed $value): bool => $value !== null && $value !== '');

            $skus[] = [
                'id' => $itemId,
                'sku_id' => $itemId,
                'price' => $resolvedPrice,
                'promotion_price' => $resolvedPrice,
                'quantity' => $this->numericOrNull($detail['stockQuantity'] ?? null),
                'properties' => $properties !== [] ? $properties : null,
            ];
        }

        return [
            'id' => $itemId,
            'mp_id' => $itemId,
            'title' => $detail['productTitle'] ?? null,
            'titleEn' => $detail['productTitle'] ?? null,
            'description' => null,
            'link' => $detail['itemUrl'] ?? null,
            'price' => $resolvedPrice,
            'promotion_price' => $resolvedPrice,
            'price_range' => [],
            'quantity' => $this->numericOrNull($detail['stockQuantity'] ?? null),
            'moq' => null,
            'unit' => null,
            'category_id' => $categoryId !== false ? $categoryId : null,
            'category_name' => $detail['categoryName'] ?? null,
            'shop_id' => $detail['shopId'] ?? $detail['venderId'] ?? null,
            'shop_name' => $detail['shopName'] ?? null,
            'seller_type' => $detail['sellerType'] ?? (isset($detail['isJdSelf']) ? ($detail['isJdSelf'] ? 'jd_self' : 'third_party') : null),
            'level' => null,
            'img_urls' => $imgUrls,
            'video_urls' => [],
            'sold' => null,
            'status' => $detail['status'] ?? 'success',
            'skus' => $skus,
            'attributes' => $attributes,
            'shipping_info' => [],
            'extra_info' => [
                'stock_state' => $detail['stockState'] ?? null,
                'price_masked' => $detail['priceMasked'] ?? null,
                'price_display' => $detail['priceDisplay'] ?? null,
                'currency' => $detail['currency'] ?? ($price['currency'] ?? 'CNY'),
                'shop_url' => $detail['shopUrl'] ?? null,
                'vender_id' => $detail['venderId'] ?? null,
                'price_available' => $price['available'] ?? null,
                'unavailable_reason' => $price['unavailableReason'] ?? null,
            ],
            '_rapidapi_detail' => $detail,
            '_rapidapi_price' => $price,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function toListItem(array $item): array
    {
        return [
            'id' => (string) ($item['itemId'] ?? ''),
            'title' => $item['productTitle'] ?? null,
            'titleEn' => $item['productTitle'] ?? null,
            'link' => $item['itemUrl'] ?? null,
            'img_url' => $item['coverUrl'] ?? null,
            'price' => $this->money($item['price'] ?? null),
            'promotion_price' => $this->money($item['price'] ?? null),
            'retail_price' => null,
            'wholesale_price' => null,
            'dropship_price' => null,
            'unit' => null,
            'sales_volume' => $item['salesText'] ?? $item['monthSalesText'] ?? null,
            'retention_rate' => $item['goodRate'] ?? null,
            'seller_type' => $item['sellerType'] ?? (isset($item['isJdSelf']) ? ($item['isJdSelf'] ? 'jd_self' : 'third_party') : null),
            'level' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $envelope
     * @return array<string, mixed>|null
     */
    public function firstDataItem(array $envelope): ?array
    {
        $data = $envelope['data'] ?? null;

        if (! is_array($data) || $data === []) {
            return null;
        }

        $first = $data[0] ?? null;

        return is_array($first) ? $first : null;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array<string, mixed>|null  $price
     */
    private function resolvePrice(array $detail, ?array $price): ?float
    {
        if ($price !== null) {
            $fromPrice = $this->money($price['price'] ?? null);
            if ($fromPrice !== null) {
                return $fromPrice;
            }
        }

        if (! empty($detail['priceMasked'])) {
            return null;
        }

        return $this->money($detail['price'] ?? null);
    }

    private function money(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function numericOrNull(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? 0 + $value : null;
    }
}
