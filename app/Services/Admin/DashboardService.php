<?php

namespace App\Services\Admin;

use App\Models\CurrencyExchangeRate;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\Platform;
use App\Models\User;
use App\Models\UserCartItem;
use App\Models\UserWallet;
use App\Models\UserWishlistItem;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /** @var list<string> */
    public const REVENUE_STATUSES = OrderStatus::FULFILLMENT_CODES;

    public function overview(): array
    {
        $monthStart = Carbon::now()->startOfMonth();

        return [
            'stats' => $this->stats($monthStart),
            'orders_by_status' => $this->ordersByStatus(),
            'orders_by_platform' => $this->ordersByPlatform(),
            'recent_orders' => $this->recentOrders(),
            'recent_customers' => $this->recentCustomers(),
            'recent_wallet_transactions' => $this->recentWalletTransactions(),
        ];
    }

    /**
     * @return array<string, int|float|bool|\Illuminate\Support\Carbon|null>
     */
    protected function stats(Carbon $monthStart): array
    {
        $revenueBaseQuery = CustomerOrder::query()->whereIn('status', self::REVENUE_STATUSES);

        return array_merge([
            'customers_total' => User::query()->count(),
            'customers_active' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
            'customers_new_month' => User::query()->where('created_at', '>=', $monthStart)->count(),
            'orders_total' => CustomerOrder::query()->count(),
            'orders_month' => CustomerOrder::query()->where('created_at', '>=', $monthStart)->count(),
            'orders_pending_payment' => CustomerOrder::query()->where('payment_status', 'unpaid')->count(),
            'orders_completed' => CustomerOrder::query()->where('status', OrderStatus::CODE_DELIVERED_TO_CUSTOMER)->count(),
            'revenue_cny_total' => (float) (clone $revenueBaseQuery)->sum('customer_total_cny'),
            'revenue_cny_month' => (float) CustomerOrder::query()
                ->whereIn('status', self::REVENUE_STATUSES)
                ->where('created_at', '>=', $monthStart)
                ->sum('customer_total_cny'),
            'revenue_tjs_total' => (float) (clone $revenueBaseQuery)->sum('customer_total_tjs'),
            'revenue_tjs_month' => (float) CustomerOrder::query()
                ->whereIn('status', self::REVENUE_STATUSES)
                ->where('created_at', '>=', $monthStart)
                ->sum('customer_total_tjs'),
            'commission_total' => (float) (clone $revenueBaseQuery)->sum('commission_amount'),
            'commission_month' => (float) CustomerOrder::query()
                ->whereIn('status', self::REVENUE_STATUSES)
                ->where('created_at', '>=', $monthStart)
                ->sum('commission_amount'),
            'wallet_balance_total' => (float) UserWallet::query()->sum('balance'),
            'wallet_transactions_month' => WalletTransaction::query()
                ->where('created_at', '>=', $monthStart)
                ->where('status', WalletTransaction::STATUS_COMPLETED)
                ->count(),
            'warehouses_total' => Warehouse::query()->count(),
            'warehouses_active' => Warehouse::query()->where('status', Warehouse::STATUS_ACTIVE)->count(),
            'platforms_total' => Platform::query()->count(),
            'platforms_available' => Platform::query()->where('is_available', true)->count(),
            'cart_items' => UserCartItem::query()->count(),
            'wishlist_items' => UserWishlistItem::query()->count(),
        ], $this->exchangeRateStats());
    }

    /**
     * @return array<string, int>
     */
    protected function ordersByStatus(): array
    {
        return CustomerOrder::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function ordersByPlatform(): array
    {
        return CustomerOrder::query()
            ->selectRaw('platform, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->pluck('total', 'platform')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function recentOrders(): Collection
    {
        return CustomerOrder::query()
            ->with('user:id,name,phone')
            ->latest()
            ->limit(8)
            ->get();
    }

    protected function recentCustomers(): Collection
    {
        return User::query()
            ->latest()
            ->limit(8)
            ->get(['id', 'name', 'phone', 'email', 'status', 'created_at', 'last_login_at']);
    }

    protected function recentWalletTransactions(): Collection
    {
        return WalletTransaction::query()
            ->with(['user:id,name,phone'])
            ->latest()
            ->limit(8)
            ->get();
    }

    /**
     * @return array<string, float|bool|\Illuminate\Support\Carbon|null>
     */
    protected function exchangeRateStats(): array
    {
        $config = CurrencyExchangeRate::query()->first();

        return [
            'exchange_rate' => $config ? (float) $config->exchange_rate : null,
            'exchange_rate_last_synced' => $config?->last_synced_at,
            'exchange_rate_auto_refresh' => (bool) ($config?->auto_refresh_enabled ?? false),
        ];
    }
}
