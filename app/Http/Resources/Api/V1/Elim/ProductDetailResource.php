<?php

namespace App\Http\Resources\Api\V1\Elim;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'platform' => $this->resource['platform'] ?? null,
            'id' => $this->resource['id'] ?? null,
            'marketplace_id' => $this->resource['marketplace_id'] ?? null,
            'mi_id' => $this->resource['mi_id'] ?? null,
            'title' => $this->resource['title'] ?? null,
            'original_title' => $this->resource['original_title'] ?? null,
            'description' => $this->resource['description'] ?? null,
            'url' => $this->resource['url'] ?? null,
            'price' => $this->resource['price'] ?? null,
            'promotion_price' => $this->resource['promotion_price'] ?? null,
            'coupon_price' => $this->resource['coupon_price'] ?? null,
            'quote_type' => $this->resource['quote_type'] ?? null,
            'price_range' => $this->resource['price_range'] ?? [],
            'sku_price_ranges' => $this->resource['sku_price_ranges'] ?? [],
            'property_images' => $this->resource['property_images'] ?? [],
            'fenxiao_sale_info' => $this->resource['fenxiao_sale_info'] ?? null,
            'seller_mix_setting' => $this->resource['seller_mix_setting'] ?? null,
            'quantity' => $this->resource['quantity'] ?? null,
            'minimum_order_quantity' => $this->resource['minimum_order_quantity'] ?? null,
            'unit' => $this->resource['unit'] ?? null,
            'category' => $this->resource['category'] ?? [],
            'shop' => $this->resource['shop'] ?? [],
            'images' => $this->resource['images'] ?? [],
            'videos' => $this->resource['videos'] ?? [],
            'sold' => $this->resource['sold'] ?? null,
            'status' => $this->resource['status'] ?? null,
            'skus' => $this->resource['skus'] ?? [],
            'attributes' => $this->resource['attributes'] ?? [],
            'shipping_info' => $this->resource['shipping_info'] ?? [],
            'extra_info' => $this->resource['extra_info'] ?? [],
            'currency' => $this->resource['currency'] ?? null,
        ];
    }
}
