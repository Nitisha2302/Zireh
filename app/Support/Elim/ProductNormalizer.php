<?php

namespace App\Support\Elim;

class ProductNormalizer
{
    public function listResponse(array $response, string $platform): array
    {
        return [
            'platform' => $platform,
            'message' => $response['message'] ?? null,
            'pagination' => [
                'total' => $response['paginate']['total'] ?? null,
                'page' => $response['paginate']['current'] ?? null,
                'size' => $response['paginate']['size'] ?? null,
            ],
            'items' => collect($response['items'] ?? [])
                ->map(fn (array $item): array => $this->listItem($item, $platform))
                ->values()
                ->all(),
            'raw' => $response,
        ];
    }

    public function detailResponse(array $response, string $platform): array
    {
        return [
            'platform' => $platform,
            'id' => (string) ($response['id'] ?? ''),
            'marketplace_id' => $response['mp_id'] ?? null,
            'title' => $response['titleEn'] ?? $response['title'] ?? null,
            'original_title' => $response['title'] ?? null,
            'description' => $response['description'] ?? null,
            'url' => $response['link'] ?? null,
            'price' => $this->money($response['price'] ?? null),
            'promotion_price' => $this->money($response['promotion_price'] ?? null),
            'price_range' => $response['price_range'] ?? [],
            'quantity' => $response['quantity'] ?? null,
            'minimum_order_quantity' => $response['moq'] ?? null,
            'unit' => $response['unit'] ?? null,
            'category' => [
                'id' => $response['category_id'] ?? null,
                'name' => $response['category_name'] ?? null,
            ],
            'shop' => [
                'id' => $response['shop_id'] ?? null,
                'name' => $response['shop_name'] ?? null,
                'seller_type' => $response['seller_type'] ?? null,
                'level' => $response['level'] ?? null,
            ],
            'images' => $response['img_urls'] ?? [],
            'videos' => $response['video_urls'] ?? [],
            'sold' => $response['sold'] ?? null,
            'status' => $response['status'] ?? null,
            'skus' => $response['skus'] ?? [],
            'attributes' => $response['attributes'] ?? [],
            'shipping_info' => $response['shipping_info'] ?? [],
            'extra_info' => $response['extra_info'] ?? [],
            'raw' => $response,
        ];
    }

    public function wishlistSnapshot(array $detail): array
    {
        $images = $detail['images'] ?? [];
        $shop = $detail['shop'] ?? [];

        return [
            'platform' => $detail['platform'] ?? null,
            'id' => (string) ($detail['id'] ?? ''),
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

    private function listItem(array $item, string $platform): array
    {
        return [
            'platform' => $platform,
            'id' => (string) ($item['id'] ?? ''),
            'title' => $item['titleEn'] ?? $item['title'] ?? null,
            'original_title' => $item['title'] ?? null,
            'url' => $item['link'] ?? null,
            'image' => $item['img_url'] ?? null,
            'price' => $this->money($item['price'] ?? null),
            'promotion_price' => $this->money($item['promotion_price'] ?? null),
            'retail_price' => $this->money($item['retail_price'] ?? null),
            'wholesale_price' => $this->money($item['whosesale_price'] ?? $item['wholesale_price'] ?? null),
            'dropship_price' => $this->money($item['dropship_price'] ?? null),
            'unit' => $item['unit'] ?? null,
            'sales_volume' => $item['sales_volume'] ?? null,
            'retention_rate' => $item['retention_rate'] ?? null,
            'seller_type' => $item['seller_type'] ?? null,
            'level' => $item['level'] ?? null,
        ];
    }

    private function money(mixed $value): float|null
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
