<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Order #{{ $order->id }}</h4>
            <p class="mb-0 text-body-secondary">{{ $order->elim_order_id ?? 'No Elim order ID' }}</p>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> Back to Orders
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Customer</h5></div>
                <div class="card-body">
                    <p class="mb-1 fw-medium">{{ $order->user?->name }}</p>
                    <p class="mb-1 text-body-secondary">{{ $order->user?->phone }}</p>
                    <p class="mb-0 text-body-secondary">{{ $order->user?->email }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Order Status</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Platform:</strong> <span class="text-uppercase">{{ $order->platform }}</span></p>
                    <p class="mb-2"><strong>Status:</strong> {{ $order->status }}</p>
                    <p class="mb-2"><strong>Payment:</strong> {{ $order->payment_status }}</p>
                    <p class="mb-0"><strong>Placed:</strong> {{ $order->created_at?->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Totals (CNY)</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Goods:</strong> ¥{{ number_format((float) $order->goods_subtotal_cny, 2) }}</p>
                    <p class="mb-2"><strong>Shipping:</strong> ¥{{ number_format((float) $order->shipping_fee_cny, 2) }}</p>
                    <p class="mb-2"><strong>Commission:</strong> ¥{{ number_format((float) $order->commission_amount, 2) }} ({{ $order->commission_percentage }}%)</p>
                    @if ($order->elim_service_fee_cny)
                        <p class="mb-2"><strong>Elim Service Fee:</strong> ¥{{ number_format((float) $order->elim_service_fee_cny, 2) }}</p>
                    @endif
                    <p class="mb-0 fw-semibold"><strong>Customer Total:</strong> ¥{{ number_format((float) $order->customer_total_cny, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5 class="mb-0">Order Items</h5></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if (! empty($item->product_snapshot['image']))
                                        <img src="{{ $item->product_snapshot['image'] }}" alt="" class="rounded" width="48" height="48" style="object-fit: cover;">
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $item->product_snapshot['title'] ?? $item->product_id }}</div>
                                        <small class="text-body-secondary">ID: {{ $item->product_id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $item->sku_id ?: '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>¥{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="fw-semibold">¥{{ number_format((float) $item->line_subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($order->remark)
        <div class="card mt-4">
            <div class="card-body">
                <strong>Remark:</strong> {{ $order->remark }}
            </div>
        </div>
    @endif
</div>
