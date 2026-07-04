<?php

namespace App\Support\Currency;

use App\Services\Currency\CurrencyExchangeService;

class CurrencyPriceConverter
{
    public function __construct(
        protected CurrencyExchangeService $currencyExchangeService,
    ) {}

    public function convert(?float $cny): ?float
    {
        return $this->currencyExchangeService->convertCnyToTjs($cny);
    }

    public function meta(): array
    {
        return $this->currencyExchangeService->meta();
    }

    public function dual(?float $cny): array
    {
        return [
            'cny' => $cny,
            'tjs' => $this->convert($cny),
        ];
    }

    public function applyToProductListItem(array $item): array
    {
        foreach (['price', 'promotion_price', 'retail_price', 'wholesale_price', 'dropship_price'] as $field) {
            if (array_key_exists($field, $item)) {
                $item[$field.'_tjs'] = $this->convert($item[$field]);
            }
        }

        return $item;
    }

    public function applyToProductDetail(array $detail): array
    {
        foreach (['price', 'promotion_price'] as $field) {
            if (array_key_exists($field, $detail)) {
                $detail[$field.'_tjs'] = $this->convert($detail[$field]);
            }
        }

        if (! empty($detail['skus']) && is_array($detail['skus'])) {
            $detail['skus'] = array_map(function ($sku): array {
                if (! is_array($sku)) {
                    return $sku;
                }

                foreach (['price', 'promotion_price'] as $field) {
                    if (isset($sku[$field]) && is_numeric($sku[$field])) {
                        $sku[$field.'_tjs'] = $this->convert((float) $sku[$field]);
                    }
                }

                return $sku;
            }, $detail['skus']);
        }

        $detail['currency'] = $this->meta();

        return $detail;
    }

    public function applyToCartSummary(array $summary): array
    {
        $summary['subtotal_tjs'] = $this->convert($summary['subtotal'] ?? null);
        $summary['total_tjs'] = $this->convert($summary['total'] ?? null);

        if (isset($summary['commission']) && is_array($summary['commission'])) {
            $summary['commission']['commission_amount_tjs'] = $this->convert(
                $summary['commission']['commission_amount'] ?? null
            );
        }

        $summary['currency'] = $this->meta();

        return $summary;
    }

    public function applyToCartItem(array $item): array
    {
        if (isset($item['unit_price'])) {
            $item['unit_price_tjs'] = $this->convert((float) $item['unit_price']);
        }

        if (isset($item['line_subtotal'])) {
            $item['line_subtotal_tjs'] = $this->convert((float) $item['line_subtotal']);
        }

        if (isset($item['product']) && is_array($item['product'])) {
            foreach (['price', 'promotion_price', 'unit_price'] as $field) {
                if (isset($item['product'][$field]) && is_numeric($item['product'][$field])) {
                    $item['product'][$field.'_tjs'] = $this->convert((float) $item['product'][$field]);
                }
            }
        }

        return $item;
    }

    public function applyToCheckout(array $payload): array
    {
        $payload['customer_total_tjs'] = $this->convert($payload['customer_total'] ?? null);
        $payload['currency'] = $this->meta();

        if (isset($payload['commission']) && is_array($payload['commission'])) {
            $payload['commission']['commission_amount_tjs'] = $this->convert(
                $payload['commission']['commission_amount'] ?? null
            );
        }

        if (isset($payload['elim_preview']) && is_array($payload['elim_preview'])) {
            $preview = $payload['elim_preview'];
            $payload['elim_preview']['goods_subtotal_tjs'] = $this->convert($preview['goods_subtotal_cny'] ?? null);
            $payload['elim_preview']['shipping_fee_tjs'] = $this->convert($preview['shipping_fee_cny'] ?? null);
            $payload['elim_preview']['service_fee_tjs'] = $this->convert($preview['service_fee_cny'] ?? null);
        }

        return $payload;
    }
}
