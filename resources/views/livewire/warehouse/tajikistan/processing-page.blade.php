<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">{{ __('admin.tajikistan_warehouse_processing') }}</h4>
            <p class="mb-0 text-body-secondary">
                @if ($assignedWarehouse)
                    {{ $assignedWarehouse->warehouse_name }} ({{ $assignedWarehouse->warehouse_code }}) ·
                @endif
                {{ __('admin.tajikistan_warehouse_processing_description') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tajikistan.scanner') }}" class="btn btn-label-info">
                <i class="icon-base ti tabler-barcode me-1"></i> {{ __('admin.parcel_barcode_scanner') }}
            </a>
            <a href="{{ route('tajikistan.exceptions.index') }}" class="btn btn-label-warning">
                <i class="icon-base ti tabler-alert-triangle me-1"></i> {{ __('admin.exception_management') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">1. {{ __('admin.scan_incoming_parcel') }}</h5>
                </div>
                <div class="card-body">
                    <form wire:submit="scanIncomingParcel" class="mb-3">
                        <label class="form-label" for="processing-scan-input">{{ __('admin.scan_barcode_or_qr_input') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="icon-base ti tabler-scan"></i></span>
                            <input
                                id="processing-scan-input"
                                type="text"
                                class="form-control @error('scanInput') is-invalid @enderror @error('order') is-invalid @enderror"
                                wire:model="scanInput"
                                placeholder="{{ __('admin.scan_barcode_placeholder') }}"
                                autofocus
                                autocomplete="off"
                            >
                            <button type="submit" class="btn btn-primary">
                                {{ __('admin.lookup_order') }}
                            </button>
                        </div>
                        @error('scanInput') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                        @error('order') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                    </form>

                    @if ($lastUnidentifiedCode)
                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold mb-2">
                                <i class="icon-base ti tabler-alert-triangle me-1"></i>
                                {{ __('admin.unidentified_parcel_prompt', ['code' => $lastUnidentifiedCode]) }}
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($exceptionTypes as $exType)
                                    <button type="button" class="btn btn-xs btn-warning" wire:click="flagException('{{ $exType }}')">
                                        + {{ __('admin.exception_type_'.$exType) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if ($order)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">#{{ $order->id }} · {{ $order->parcel_code }}</h5>
                        @if ($order->orderStatus)
                            <span class="badge {{ $order->orderStatus->badgeClass() }}">{{ $order->orderStatus->name }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>{{ __('admin.elim_order_id') }}:</strong> {{ $order->elim_order_id ?? '—' }}</p>
                        <p class="mb-1"><strong>{{ __('admin.parcel_tracking_id') }}:</strong> {{ $order->parcel_tracking_id ?? '—' }}</p>
                        <p class="mb-1"><strong>{{ __('admin.customer') }}:</strong> {{ $order->user?->name ?? '—' }} ({{ $order->user?->phone ?? '—' }})</p>
                        <p class="mb-3"><strong>{{ __('admin.platforms') }}:</strong> <span class="text-uppercase">{{ $order->platform }}</span></p>

                        <div class="d-flex flex-wrap gap-2 border-top pt-3">
                            <button
                                type="button"
                                class="btn btn-xs btn-label-danger"
                                wire:click="flagException('damaged_parcel')"
                            >
                                <i class="icon-base ti tabler-alert-triangle me-1"></i>
                                {{ __('admin.exception_type_damaged_parcel') }}
                            </button>
                            <a href="{{ route('tajikistan.orders.show', $order) }}" class="btn btn-xs btn-label-secondary">
                                {{ __('admin.view') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            @if ($order)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">2. {{ __('admin.record_weight_and_dimensions') }}</h5>
                        <small class="text-body-secondary">{{ __('admin.auto_shipping_calculation_hint') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('admin.shipping_method') }}</label>
                                <select class="form-select @error('shippingMethodId') is-invalid @enderror" wire:model.live="shippingMethodId">
                                    <option value="">{{ __('admin.select_shipping_method') }}</option>
                                    @foreach ($shippingMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }} ({{ $method->code }})</option>
                                    @endforeach
                                </select>
                                @error('shippingMethodId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label d-block">{{ __('admin.pickup_measurement_mode') }}</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" id="proc-mode-weight" value="weight" wire:model.live="measurementMode">
                                    <label class="btn btn-outline-primary" for="proc-mode-weight">{{ __('admin.pickup_mode_weight') }}</label>
                                    <input type="radio" class="btn-check" id="proc-mode-cm" value="cm" wire:model.live="measurementMode">
                                    <label class="btn btn-outline-primary" for="proc-mode-cm">{{ __('admin.pickup_mode_cm') }}</label>
                                    <input type="radio" class="btn-check" id="proc-mode-both" value="both" wire:model.live="measurementMode">
                                    <label class="btn btn-outline-primary" for="proc-mode-both">{{ __('admin.pickup_mode_both') }}</label>
                                </div>
                            </div>

                            @if ($measurementMode === 'weight' || $measurementMode === 'both')
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('admin.package_weight_g') }}</label>
                                    <input type="number" step="1" class="form-control @error('packageWeightG') is-invalid @enderror" wire:model.live.debounce.250ms="packageWeightG">
                                    @error('packageWeightG') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            @if ($measurementMode === 'cm' || $measurementMode === 'both')
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('admin.package_length_cm') }}</label>
                                    <input type="number" step="0.01" class="form-control @error('packageLengthCm') is-invalid @enderror" wire:model.live.debounce.250ms="packageLengthCm">
                                    @error('packageLengthCm') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('admin.package_width_cm') }}</label>
                                    <input type="number" step="0.01" class="form-control @error('packageWidthCm') is-invalid @enderror" wire:model.live.debounce.250ms="packageWidthCm">
                                    @error('packageWidthCm') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('admin.package_height_cm') }}</label>
                                    <input type="number" step="0.01" class="form-control @error('packageHeightCm') is-invalid @enderror" wire:model.live.debounce.250ms="packageHeightCm">
                                    @error('packageHeightCm') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            @endif
                        </div>

                        @if ($shippingPreview)
                            <div class="border rounded p-3 mt-4 bg-label-primary">
                                <h6 class="mb-2">3. {{ __('admin.pickup_shipping_preview') }}</h6>
                                <div class="row g-2 small">
                                    <div class="col-md-6">
                                        <div><strong>{{ __('admin.shipping_method') }}:</strong> {{ $shippingPreview['method']['name'] ?? '—' }}</div>
                                        @if (in_array($shippingPreview['applied_method'] ?? null, ['weight', 'both'], true))
                                            <div><strong>{{ __('admin.package_weight_g') }}:</strong> {{ number_format(($shippingPreview['package']['weight_kg'] ?? 0) * 1000, 0) }} g</div>
                                        @endif
                                        @if (in_array($shippingPreview['applied_method'] ?? null, ['volume', 'both'], true))
                                            <div><strong>{{ __('admin.package_volume_m3') }}:</strong> {{ number_format($shippingPreview['package']['volume_m3'] ?? 0, 4) }} m³</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if (in_array($shippingPreview['applied_method'] ?? null, ['weight', 'both'], true))
                                            <div><strong>{{ __('admin.weight_cost') }}:</strong> {{ number_format($shippingPreview['weight_cost_tjs'], 2) }} TJS</div>
                                        @endif
                                        @if (in_array($shippingPreview['applied_method'] ?? null, ['volume', 'both'], true))
                                            <div><strong>{{ __('admin.volume_cost') }}:</strong> {{ number_format($shippingPreview['volume_cost_tjs'], 2) }} TJS</div>
                                        @endif
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="fs-5 fw-bold text-primary">
                                    {{ __('admin.final_pickup_shipping') }}: {{ number_format($shippingPreview['shipping_cost'], 2) }} TJS
                                </div>
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-4">
                            <button
                                type="button"
                                class="btn btn-success"
                                wire:click="completeProcessing"
                                wire:loading.attr="disabled"
                                wire:target="completeProcessing"
                            >
                                <i class="icon-base ti tabler-check me-1"></i>
                                {{ __('admin.complete_processing_ready_for_pickup') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('admin.processed_parcels_session_log') }} ({{ count($processedHistory) }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin.time') }}</th>
                                <th>{{ __('admin.orders') }}</th>
                                <th>{{ __('admin.customer') }}</th>
                                <th>{{ __('admin.package_weight_g') }}</th>
                                <th>{{ __('admin.pickup_shipping') }}</th>
                                <th>{{ __('admin.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($processedHistory as $item)
                                <tr>
                                    <td class="small text-body-secondary">{{ $item['processed_at'] }}</td>
                                    <td>
                                        <div class="fw-semibold">#{{ $item['order_id'] }} · {{ $item['parcel_code'] }}</div>
                                        <small class="text-body-secondary">{{ $item['elim_order_id'] ?? '—' }}</small>
                                    </td>
                                    <td>{{ $item['customer_name'] }}</td>
                                    <td>{{ $item['weight_g'] ? number_format((float) $item['weight_g'], 0).' g' : $item['dimensions_cm'] }}</td>
                                    <td class="fw-semibold">{{ number_format((float) $item['shipping_fee_tjs'], 2) }} TJS</td>
                                    <td><span class="badge bg-label-success">{{ $item['status_name'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-body-secondary">
                                        {{ __('admin.no_processed_parcels_yet') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('processing-scanner-refocus', () => {
        setTimeout(() => {
            document.getElementById('processing-scan-input')?.focus();
        }, 50);
    });
</script>
@endscript
