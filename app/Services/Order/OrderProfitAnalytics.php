<?php

namespace App\Services\Order;

use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Services\Currency\CurrencyExchangeService;

class OrderProfitAnalytics
{
    public function __construct(
        private readonly CurrencyExchangeService $currencyExchangeService,
    ) {}

    /**
     * @return array{
     *     rate: float,
     *     revenue_tjs: float,
     *     goods_cost_tjs: float,
     *     fee_cost_tjs: float,
     *     profit_tjs: float,
     *     margin_percent: float,
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

        return [
            'rate' => $rate,
            'revenue_tjs' => $revenueTjs,
            'goods_cost_tjs' => round($goodsCostTjs, 2),
            'fee_cost_tjs' => $feeCostTjs,
            'profit_tjs' => $profitTjs,
            'margin_percent' => $this->marginPercent($profitTjs, $revenueTjs),
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
}
