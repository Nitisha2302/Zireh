@php
    $orderStatusLabels = [
        'creating' => __('admin.order_status_creating'),
        'pending_payment' => __('admin.order_status_pending_payment'),
        'paid' => __('admin.order_status_paid'),
        'shipped' => __('admin.order_status_shipped'),
        'completed' => __('admin.order_status_completed'),
        'cancelled' => __('admin.order_status_cancelled'),
    ];

    $orderStatusColors = [
        'creating' => 'bg-label-secondary',
        'pending_payment' => 'bg-label-warning',
        'paid' => 'bg-label-info',
        'shipped' => 'bg-label-primary',
        'completed' => 'bg-label-success',
        'cancelled' => 'bg-label-danger',
    ];

    $orderStatusBarColors = [
        'creating' => 'bg-secondary',
        'pending_payment' => 'bg-warning',
        'paid' => 'bg-info',
        'shipped' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.dashboard_welcome', ['name' => $admin?->name ?? __('admin.dashboard')]) }}
            </h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.dashboard_subtitle') }}</p>
        </div>
        <div class="text-body-secondary small">
            {{ now()->format('l, M d, Y') }}
        </div>
    </div>

    {{-- Primary KPIs --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-body-secondary">{{ __('admin.dashboard_customers') }}</p>
                            <h3 class="mb-0">{{ number_format($stats['customers_total']) }}</h3>
                            <small class="text-success">{{ number_format($stats['customers_active']) }}
                                {{ __('admin.active') }}</small>
                            <div class="text-body-secondary small mt-1">
                                +{{ number_format($stats['customers_new_month']) }} {{ __('admin.this_month') }}</div>
                        </div>
                        <span class="badge bg-label-primary p-2">
                            <i class="icon-base ti tabler-users"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-body-secondary">{{ __('admin.dashboard_orders') }}</p>
                            <h3 class="mb-0">{{ number_format($stats['orders_total']) }}</h3>
                            <small class="text-body-secondary">{{ number_format($stats['orders_completed']) }}
                                {{ __('admin.completed') }}</small>
                            <div class="text-body-secondary small mt-1">+{{ number_format($stats['orders_month']) }}
                                {{ __('admin.this_month') }}</div>
                        </div>
                        <span class="badge bg-label-info p-2">
                            <i class="icon-base ti tabler-receipt"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-body-secondary">{{ __('admin.dashboard_revenue_tjs') }}</p>
                            <h3 class="mb-0">{{ number_format($stats['revenue_tjs_total'], 2) }} TJS</h3>
                            <small class="text-body-secondary">{{ number_format($stats['revenue_tjs_month'], 2) }} TJS
                                {{ __('admin.this_month') }}</small>
                            <div class="text-body-secondary small mt-1">
                                ¥{{ number_format($stats['revenue_cny_total'], 2) }} {{ __('admin.total') }}</div>
                        </div>
                        <span class="badge bg-label-success p-2">
                            <i class="icon-base ti tabler-currency-dollar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 text-body-secondary">{{ __('admin.dashboard_commission') }}</p>
                            <h3 class="mb-0">¥{{ number_format($stats['commission_total'], 2) }}</h3>
                            <small class="text-body-secondary">¥{{ number_format($stats['commission_month'], 2) }}
                                {{ __('admin.this_month') }}</small>
                        </div>
                        <span class="badge bg-label-warning p-2">
                            <i class="icon-base ti tabler-percentage"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="icon-base ti tabler-clock-dollar"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.dashboard_pending_payments') }}</p>
                        <h4 class="mb-0">{{ number_format($stats['orders_pending_payment']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="icon-base ti tabler-wallet"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.dashboard_wallet_balance') }}</p>
                        <h4 class="mb-0">сом. {{ number_format($stats['wallet_balance_total'], 2) }}</h4>
                        <small class="text-body-secondary">{{ number_format($stats['wallet_transactions_month']) }}
                            {{ __('admin.dashboard_txn_this_month') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="icon-base ti tabler-building-warehouse"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.warehouse_stats_active') }}</p>
                        <h4 class="mb-0">{{ number_format($stats['warehouses_active']) }} /
                            {{ number_format($stats['warehouses_total']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="icon-base ti tabler-arrows-exchange"></i>
                        </span>
                    </div>
                    <div>
                        <p class="mb-0 text-body-secondary small">{{ __('admin.current_exchange_rate') }}</p>
                        <h4 class="mb-0">
                            @if ($stats['exchange_rate'])
                                1 CNY = {{ number_format($stats['exchange_rate'], 4) }} TJS
                            @else
                                {{ __('admin.not_available') }}
                            @endif
                        </h4>
                        <small class="text-body-secondary">
                            @if ($stats['exchange_rate_auto_refresh'])
                                {{ __('admin.enabled') }}
                            @else
                                {{ __('admin.disabled') }}
                            @endif
                            @if ($stats['exchange_rate_last_synced'])
                                · {{ $stats['exchange_rate_last_synced']->diffForHumans() }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown + Engagement --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('admin.dashboard_orders_by_status') }}</h5>
                    <a href="{{ route('admin.orders.index') }}"
                        class="btn btn-sm btn-label-primary">{{ __('admin.view_all') }}</a>
                </div>
                <div class="card-body">
                    @forelse ($ordersByStatus as $status => $count)
                        @php
                            $percent = $stats['orders_total'] > 0 ? round(($count / $stats['orders_total']) * 100) : 0;
                            $label = $orderStatusLabels[$status] ?? str($status)->replace('_', ' ')->title();
                            $barColor = $orderStatusBarColors[$status] ?? 'bg-primary';
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">{{ $label }}</span>
                                <span class="small fw-semibold">{{ number_format($count) }}
                                    ({{ $percent }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $barColor }}" style="width: {{ $percent }}%">
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-body-secondary mb-0 text-center py-4">{{ __('admin.dashboard_no_orders') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.dashboard_overview') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-body-secondary small">{{ __('admin.platforms') }}</p>
                                <h5 class="mb-0">{{ number_format($stats['platforms_available']) }} /
                                    {{ number_format($stats['platforms_total']) }}</h5>
                                <small class="text-body-secondary">{{ __('admin.available') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-body-secondary small">{{ __('admin.dashboard_cart_items') }}</p>
                                <h5 class="mb-0">{{ number_format($stats['cart_items']) }}</h5>
                                <small class="text-body-secondary">{{ __('admin.dashboard_engagement') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-body-secondary small">{{ __('admin.dashboard_wishlist_items') }}
                                </p>
                                <h5 class="mb-0">{{ number_format($stats['wishlist_items']) }}</h5>
                                <small class="text-body-secondary">{{ __('admin.dashboard_engagement') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-body-secondary small">
                                    {{ __('admin.dashboard_orders_by_platform') }}</p>
                                @forelse ($ordersByPlatform as $platform => $count)
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-label-info text-uppercase">{{ $platform }}</span>
                                        <span class="fw-semibold">{{ number_format($count) }}</span>
                                    </div>
                                @empty
                                    <span
                                        class="text-body-secondary small">{{ __('admin.dashboard_no_orders') }}</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-label-primary">
                        <i class="icon-base ti tabler-receipt me-1"></i>{{ __('admin.dashboard_orders') }}
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-label-secondary">
                        <i class="icon-base ti tabler-users me-1"></i>{{ __('admin.dashboard_customers') }}
                    </a>
                    <a href="{{ route('admin.wallet-transactions.index') }}" class="btn btn-label-secondary">
                        <i class="icon-base ti tabler-wallet me-1"></i>{{ __('admin.dashboard_wallet_transactions') }}
                    </a>
                    <a href="{{ route('admin.warehouses.index') }}" class="btn btn-label-secondary">
                        <i
                            class="icon-base ti tabler-building-warehouse me-1"></i>{{ __('admin.warehouse_management') }}
                    </a>
                    <a href="{{ route('admin.settings.currency-exchange') }}" class="btn btn-label-secondary">
                        <i
                            class="icon-base ti tabler-arrows-exchange me-1"></i>{{ __('admin.currency_exchange_settings') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('admin.dashboard_recent_orders') }}</h5>
                    <a href="{{ route('admin.orders.index') }}"
                        class="btn btn-sm btn-label-primary">{{ __('admin.view_all') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('admin.dashboard_customers') }}</th>
                                <th>{{ __('admin.platforms') }}</th>
                                <th>{{ __('admin.status') }}</th>
                                <th>CNY</th>
                                <th>TJS</th>
                                <th>{{ __('admin.created_date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td><span class="fw-semibold">#{{ $order->id }}</span></td>
                                    <td>
                                        <div class="fw-medium">{{ $order->user?->name ?? 'Unnamed customer' }}</div>
                                        <small class="text-body-secondary">{{ $order->user?->phone }}</small>
                                    </td>
                                    <td><span class="badge bg-label-info text-uppercase">{{ $order->platform }}</span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $orderStatusColors[$order->status] ?? 'bg-label-secondary' }}">
                                            {{ $orderStatusLabels[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="fw-semibold">
                                        ¥{{ number_format((float) $order->customer_total_cny, 2) }}</td>
                                    <td>
                                        @if ($order->customer_total_tjs)
                                            {{ number_format((float) $order->customer_total_tjs, 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $order->created_at?->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.orders.show', $order) }}"
                                            class="btn btn-sm btn-icon btn-text-secondary">
                                            <i class="icon-base ti tabler-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-body-secondary">
                                        {{ __('admin.dashboard_no_orders') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('admin.dashboard_recent_customers') }}</h5>
                    <a href="{{ route('admin.customers.index') }}"
                        class="btn btn-sm btn-label-primary">{{ __('admin.view_all') }}</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentCustomers as $customer)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-medium">{{ $customer->name ?: 'Unnamed customer' }}</div>
                                <small
                                    class="text-body-secondary">{{ $customer->phone ?: $customer->email ?: __('admin.not_added') }}</small>
                            </div>
                            <span
                                class="badge {{ $customer->status === 'active' ? 'bg-label-success' : 'bg-label-secondary' }}">
                                {{ $customer->status }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-body-secondary py-4">
                            {{ __('admin.dashboard_no_customers') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('admin.dashboard_wallet_transactions') }}</h5>
                    <a href="{{ route('admin.wallet-transactions.index') }}"
                        class="btn btn-sm btn-label-primary">{{ __('admin.view_all') }}</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentWalletTransactions as $transaction)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-medium">{{ $transaction->user?->name ?? 'Unnamed customer' }}</div>
                                    <small
                                        class="text-body-secondary">{{ str($transaction->source)->replace('_', ' ')->title() }}</small>
                                </div>
                                <span
                                    class="fw-semibold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type === 'credit' ? '+' : '-' }}сом. {{ number_format((float) $transaction->amount, 2) }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-body-secondary py-4">
                            {{ __('admin.dashboard_no_transactions') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
