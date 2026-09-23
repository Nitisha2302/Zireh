<?php

namespace App\Services\Order;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use App\Services\Currency\CurrencyExchangeService;
use App\Services\PlatformCommissionService;

class OrderProfitAnalytics
{
    public function __construct(
        private readonly CurrencyExchangeService $currencyExchangeService,
        private readonly PlatformCommissionService $platformCommissionService,
    ) {}

    /**
     * @return array{
     *     rate: float,
     *     revenue_tjs: float,
     *     goods_cost_tjs: float,
     *     fee_cost_tjs: float,
     *     profit_tjs: float,
     *     margin_percent: float,
     *     goods_cny: float,
     *     commission_slab_id: int|null,
     *     commission_percentage: float|null,
     *     commission_min_amount: float|null,
     *     commission_max_amount: float|null,
     *     commission_range_label: string|null,
     *     items: array<int, array{
     *         id: int,
     *         unit_price_cny: float,
     *         line_subtotal_cny: float,
     *         cost_tjs: float,
     *         sell_tjs: float,
     *         profit_tjs: float,
     *         margin_percent: float
     *     }>
     * }
     */
    public function forOrder(CustomerOrder $order): array
    {
        $order->loadMissing('items');

        $rate = $this->rateFor($order);
        $items = [];
        $goodsCostTjs = 0.0;
        $itemProfitTotal = 0.0;

        foreach ($order->items as $item) {
            $row = $this->forItem($item, $rate);
            $items[$item->id] = $row;
            $goodsCostTjs += $row['cost_tjs'];
            $itemProfitTotal += $row['profit_tjs'];
        }

        $feeCny = (float) $order->shipping_fee_cny + (float) ($order->elim_service_fee_cny ?? 0);
        $feeCostTjs = round($feeCny * $rate, 2);
        $revenueTjs = $order->paymentAmountTjs();
        $profitTjs = round($itemProfitTotal - $feeCostTjs, 2);
        $slab = $this->resolveSlab($order);

        return [
            'rate' => $rate,
            'revenue_tjs' => $revenueTjs,
            'goods_cost_tjs' => round($goodsCostTjs, 2),
            'fee_cost_tjs' => $feeCostTjs,
            'profit_tjs' => $profitTjs,
            'margin_percent' => $this->marginPercent($profitTjs, $revenueTjs),
            'goods_cny' => $this->goodsAmountCny($order),
            ...$slab,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     unit_price_cny: float,
     *     line_subtotal_cny: float,
     *     cost_tjs: float,
     *     sell_tjs: float,
     *     profit_tjs: float,
     *     margin_percent: float
     * }
     */
    public function forItem(CustomerOrderItem $item, float $rate): array
    {
        $costTjs = round((float) $item->line_subtotal * $rate, 2);
        $sellTjs = round((float) ($item->final_amount_tjs ?? 0), 2);
        $profitTjs = round($sellTjs - $costTjs, 2);

        return [
            'id' => $item->id,
            'unit_price_cny' => (float) $item->unit_price,
            'line_subtotal_cny' => (float) $item->line_subtotal,
            'cost_tjs' => $costTjs,
            'sell_tjs' => $sellTjs,
            'profit_tjs' => $profitTjs,
            'margin_percent' => $this->marginPercent($profitTjs, $sellTjs),
        ];
    }

    public function rateFor(CustomerOrder $order): float
    {
        $stored = $order->exchange_rate;

        if ($stored !== null && (float) $stored > 0) {
            return (float) $stored;
        }

        return $this->currencyExchangeService->getRate();
    }

    protected function marginPercent(float $profit, float $revenue): float
    {
        if ($revenue <= 0) {
            return 0.0;
        }

        return round($profit / $revenue * 100, 2);
    }

    public function goodsAmountCny(CustomerOrder $order): float
    {
        $goods = (float) $order->goods_subtotal_cny;

        if ($goods > 0) {
            return round($goods, 2);
        }

        $order->loadMissing('items');

        return round((float) $order->items->sum('line_subtotal'), 2);
    }

    /**
     * @return array{
     *     commission_slab_id: int|null,
     *     commission_percentage: float|null,
     *     commission_min_amount: float|null,
     *     commission_max_amount: float|null,
     *     commission_range_label: string|null
     * }
     */
    protected function resolveSlab(CustomerOrder $order): array
    {
        $order->loadMissing(['commissionSlab', 'platformModel']);

        $stored = $order->commissionSlab;
        $goodsCny = $this->goodsAmountCny($order);

        if ($stored) {
            return $this->slabPayload($stored, (float) ($order->commission_percentage ?: $stored->commission_percentage));
        }

        if ((float) $order->commission_percentage > 0) {
            return [
                'commission_slab_id' => $order->commission_slab_id ? (int) $order->commission_slab_id : null,
                'commission_percentage' => (float) $order->commission_percentage,
                'commission_min_amount' => null,
                'commission_max_amount' => null,
                'commission_range_label' => null,
            ];
        }

        $platformId = $order->platform_id ?: $order->platformModel?->id;

        if (! $platformId && $order->platform) {
            $platformId = Platform::query()->where('code', $order->platform)->value('id');
        }

        if (! $platformId) {
            return $this->emptySlabPayload();
        }

        $detected = $this->platformCommissionService->findActiveSlabOrNull($platformId, $goodsCny);

        if (! $detected) {
            return $this->emptySlabPayload();
        }

        return $this->slabPayload($detected, (float) $detected->commission_percentage);
    }

    /**
     * @return array{
     *     commission_slab_id: int|null,
     *     commission_percentage: float|null,
     *     commission_min_amount: float|null,
     *     commission_max_amount: float|null,
     *     commission_range_label: string|null
     * }
     */
    protected function slabPayload(PlatformCommissionSlab $slab, float $percentage): array
    {
        $min = (float) $slab->min_amount;
        $max = $slab->max_amount !== null ? (float) $slab->max_amount : null;

        return [
            'commission_slab_id' => $slab->id,
            'commission_percentage' => $percentage,
            'commission_min_amount' => $min,
            'commission_max_amount' => $max,
            'commission_range_label' => $max === null
                ? number_format($min, 2).'+ CNY'
                : number_format($min, 2).'–'.number_format($max, 2).' CNY',
        ];
    }

    /**
     * @return array{
     *     commission_slab_id: int|null,
     *     commission_percentage: float|null,
     *     commission_min_amount: float|null,
     *     commission_max_amount: float|null,
     *     commission_range_label: string|null
     * }
     */
    protected function emptySlabPayload(): array
    {
        return [
            'commission_slab_id' => null,
            'commission_percentage' => null,
            'commission_min_amount' => null,
            'commission_max_amount' => null,
            'commission_range_label' => null,
        ];
    }
}
