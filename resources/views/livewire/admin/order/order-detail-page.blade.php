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
                <div class="card-header"><h5 class="mb-0">{{ __('admin.order_status') }}</h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong>{{ __('admin.platforms') }}:</strong> <span class="text-uppercase">{{ $order->platform }}</span></p>
                    <p class="mb-2">
                        <strong>{{ __('admin.status') }}:</strong>
                        @if ($order->orderStatus)
                            <span class="badge {{ $order->orderStatus->badgeClass() }}">{{ $order->orderStatus->name }}</span>
                        @else
                            {{ $order->status }}
                        @endif
                    </p>
                    <p class="mb-2"><strong>{{ __('admin.payment') }}:</strong> {{ $order->payment_status }} ({{ $order->payment_method }})</p>
                    @if ($order->is_demo_order)
                        <p class="mb-2"><span class="badge bg-label-warning">Demo Order</span></p>
                    @endif
                    <p class="mb-3"><strong>{{ __('admin.created_date') }}:</strong> {{ $order->created_at?->format('M d, Y H:i') }}</p>

                    <form wire:submit="updateStatus" class="border-top pt-3">
                        <label class="form-label">{{ __('admin.change_order_status') }}</label>
                        <select class="form-select mb-2 @error('statusCode') is-invalid @enderror" wire:model="statusCode">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->code }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        @error('statusCode') <div class="invalid-feedback d-block mb-2">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-sm btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updateStatus">{{ __('admin.update_order_status') }}</span>
                            <span wire:loading wire:target="updateStatus">{{ __('admin.saving') }}...</span>
                        </button>
                    </form>
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
                    @if ($order->cargo_shipping_fee_tjs)
                        <p class="mb-2"><strong>Cargo Shipping:</strong> {{ number_format((float) $order->cargo_shipping_fee_tjs, 2) }} TJS</p>
                    @endif
                    <p class="mb-0 fw-semibold"><strong>Customer Total:</strong> ¥{{ number_format((float) $order->customer_total_cny, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-0">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('admin.warehouse') }}</h5></div>
                <div class="card-body">
                    @php $warehouse = $order->warehouse ?? $order->warehouse_snapshot; @endphp
                    @if (is_array($warehouse) || $order->warehouse)
                        <p class="mb-1 fw-medium">{{ data_get($warehouse, 'warehouse_name', '—') }}</p>
                        <p class="mb-1 text-body-secondary">{{ data_get($warehouse, 'address') }}</p>
                        <p class="mb-1 text-body-secondary">{{ data_get($warehouse, 'city') }}, {{ data_get($warehouse, 'country', 'Tajikistan') }}</p>
                        <p class="mb-0 text-body-secondary">{{ data_get($warehouse, 'contact_person') }} · {{ data_get($warehouse, 'contact_number') }}</p>
                    @else
                        <p class="mb-0 text-body-secondary">—</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">Customer Delivery Address</h5></div>
                <div class="card-body">
                    @php $address = $order->userAddress ?? $order->address_snapshot; @endphp
                    @if (is_array($address) || $order->userAddress)
                        <p class="mb-1 fw-medium">{{ data_get($address, 'full_name', '—') }}</p>
                        <p class="mb-1 text-body-secondary">{{ data_get($address, 'phone') }}</p>
                        <p class="mb-1">{{ data_get($address, 'address_line_1') }}</p>
                        @if (data_get($address, 'address_line_2'))
                            <p class="mb-1">{{ data_get($address, 'address_line_2') }}</p>
                        @endif
                        <p class="mb-0 text-body-secondary">{{ data_get($address, 'city') }}, {{ data_get($address, 'state') }} {{ data_get($address, 'postal_code') }}</p>
                    @else
                        <p class="mb-0 text-body-secondary">—</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($order->shippingMethod)
        <div class="card mt-4">
            <div class="card-body">
                <strong>Shipping Method:</strong> {{ $order->shippingMethod->name }} ({{ $order->shippingMethod->code }})
            </div>
        </div>
    @endif

    @if ($order->receiver_address)
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">China Receiver Address (Elim)</h5></div>
            <div class="card-body">
                <p class="mb-1">{{ $order->receiver_address['name'] ?? '' }} · {{ $order->receiver_address['mobile'] ?? '' }}</p>
                <p class="mb-0">{{ $order->receiver_address['address'] ?? '' }}, {{ $order->receiver_address['city'] ?? '' }}</p>
            </div>
        </div>
    @endif

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
