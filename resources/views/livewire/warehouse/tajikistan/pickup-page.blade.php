<div class="container-xxl flex-grow-1 container-p-y">    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ __('admin.pickup_qr_scan') }}</h4>
            <p class="mb-0 text-body-secondary">{{ __('admin.pickup_qr_scan_hint') }}</p>
        </div>
        <a href="{{ route('tajikistan.orders.index') }}" class="btn btn-label-secondary">
            <i class="icon-base ti tabler-arrow-left me-1"></i> {{ __('admin.back_to_orders') }}
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link active"
                        id="pickup-scan-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#pickup-scan-pane"
                        type="button"
                        role="tab"
                        aria-controls="pickup-scan-pane"
                        aria-selected="true"
                    >
                        <i class="icon-base ti tabler-camera me-1"></i>
                        {{ __('admin.pickup_scan_tab') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link"
                        id="pickup-manual-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#pickup-manual-pane"
                        type="button"
                        role="tab"
                        aria-controls="pickup-manual-pane"
                        aria-selected="false"
                    >
                        <i class="icon-base ti tabler-keyboard me-1"></i>
                        {{ __('admin.pickup_manual_tab') }}
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pickup-scan-pane" role="tabpanel" aria-labelledby="pickup-scan-tab">
                    <p class="text-body-secondary mb-3">{{ __('admin.scan_qr_hint') }}</p>

                    <div
                        id="pickup-camera-alert"
                        class="alert alert-danger d-none"
                        role="alert"
                        data-denied-message="{{ __('admin.camera_permission_denied') }}"
                        data-insecure-message="{{ __('admin.camera_insecure_context') }}"
                        data-not-found-message="{{ __('admin.camera_not_found') }}"
                        data-failed-message="{{ __('admin.camera_start_failed') }}"
                    ></div>

                    <div wire:ignore class="mb-3 d-flex justify-content-center">
                        <div id="pickup-qr-reader" class="pickup-qr-reader rounded border overflow-hidden bg-dark"></div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button type="button" id="pickup-start-camera" class="btn btn-primary">
                            <i class="icon-base ti tabler-camera me-1"></i>
                            {{ __('admin.start_camera') }}
                        </button>
                        <button type="button" id="pickup-stop-camera" class="btn btn-label-secondary" disabled>
                            <i class="icon-base ti tabler-camera-off me-1"></i>
                            {{ __('admin.stop_camera') }}
                        </button>
                    </div>
                </div>

                <div class="tab-pane fade" id="pickup-manual-pane" role="tabpanel" aria-labelledby="pickup-manual-tab">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-9">
                            <label class="form-label">{{ __('admin.pickup_token') }}</label>
                            <input
                                type="text"
                                class="form-control @error('pickupToken') is-invalid @enderror"
                                wire:model="pickupToken"
                                placeholder="cargo-pickup:..."
                            >
                            @error('pickupToken') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100" wire:click="lookupOrder">
                                {{ __('admin.lookup_order') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @error('pickup_token')
                <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if ($order)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('admin.tajikistan_warehouse_order') }} #{{ $order->id }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>{{ __('admin.customer') }}:</strong> {{ $order->user?->name }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $order->user?->phone }}</p>
                        <p class="mb-1"><strong>{{ __('admin.order_status') }}:</strong> {{ $order->orderStatus?->name ?? $order->status }}</p>
                        <p class="mb-1"><strong>{{ __('admin.pickup_payment_status') }}:</strong> {{ $order->pickup_payment_status ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>{{ __('admin.package_weight_kg') }}:</strong> {{ $order->package_weight_kg }} kg</p>
                        <p class="mb-1"><strong>{{ __('admin.package_length_cm') }}:</strong> {{ $order->package_length_cm }} × {{ $order->package_width_cm }} × {{ $order->package_height_cm }} cm</p>
                        <p class="mb-1"><strong>{{ __('admin.pickup_shipping') }}:</strong> {{ number_format((float) $order->pickup_shipping_fee_tjs, 2) }} TJS</p>
                        <p class="mb-0"><strong>{{ __('admin.applied_method') }}:</strong> {{ ucfirst($order->pickup_shipping_calculation_method ?? '—') }}</p>
                    </div>
                </div>

                <h6 class="mt-4 mb-3">{{ __('admin.order_items') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width: 72px;"></th>
                                <th>{{ __('admin.product') }}</th>
                                <th>{{ __('admin.quantity') }}</th>
                                <th>{{ __('admin.unit_price') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                @php
                                    $snapshot = is_array($item->product_snapshot) ? $item->product_snapshot : [];
                                    $title = is_string($snapshot['title'] ?? null)
                                        ? $snapshot['title']
                                        : (string) ($item->product_id ?? '—');
                                    $image = is_string($snapshot['image'] ?? null) ? $snapshot['image'] : null;

                                    $formatAttrValue = function ($value) use (&$formatAttrValue): string {
                                        if (is_null($value) || is_bool($value)) {
                                            return '';
                                        }

                                        if (is_scalar($value)) {
                                            return (string) $value;
                                        }

                                        if (is_array($value)) {
                                            return collect($value)
                                                ->map(fn ($item, $key) => is_string($key) && ! is_numeric($key)
                                                    ? $key.': '.$formatAttrValue($item)
                                                    : $formatAttrValue($item))
                                                ->filter()
                                                ->implode(', ');
                                        }

                                        return '';
                                    };

                                    $formatAttrList = function ($attrs) use ($formatAttrValue): ?string {
                                        if (! is_array($attrs) || $attrs === []) {
                                            return is_string($attrs) ? $attrs : null;
                                        }

                                        $label = collect($attrs)
                                            ->map(function ($value, $key) use ($formatAttrValue) {
                                                $formatted = $formatAttrValue($value);

                                                if ($formatted === '') {
                                                    return null;
                                                }

                                                return is_string($key) && ! is_numeric($key)
                                                    ? $key.': '.$formatted
                                                    : $formatted;
                                            })
                                            ->filter()
                                            ->implode(', ');

                                        return $label !== '' ? $label : null;
                                    };

                                    $skuLabel = $formatAttrList(data_get($snapshot, 'sku.properties'));

                                    if (! $skuLabel) {
                                        $skuLabel = $formatAttrList($item->selected_attributes);
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        @if ($image)
                                            <img
                                                src="{{ $image }}"
                                                alt="{{ $title }}"
                                                class="rounded border"
                                                style="width: 56px; height: 56px; object-fit: cover;"
                                            >
                                        @else
                                            <div
                                                class="rounded border bg-label-secondary d-flex align-items-center justify-content-center"
                                                style="width: 56px; height: 56px;"
                                            >
                                                <i class="icon-base ti tabler-photo"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ $title }}</div>
                                        @if ($skuLabel)
                                            <small class="text-body-secondary">{{ $skuLabel }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>¥{{ number_format((float) $item->unit_price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-4">
                    @if ($order->pickup_payment_status === \App\Models\CustomerOrder::PICKUP_PAYMENT_STATUS_PENDING)
                        <button type="button" class="btn btn-warning" wire:click="markPaymentReceived">
                            {{ __('admin.payment_received') }}
                        </button>
                    @endif
                    @if ($order->pickup_payment_status === \App\Models\CustomerOrder::PICKUP_PAYMENT_STATUS_PAID && $order->status === \App\Models\OrderStatus::CODE_READY_FOR_PICKUP)
                        <button type="button" class="btn btn-success" wire:click="deliverOrder">
                            {{ __('admin.deliver_order') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

@assets
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
    .pickup-qr-reader {
        width: 100%;
        max-width: 320px;
        height: 240px;
    }

    .pickup-qr-reader video,
    .pickup-qr-reader canvas,
    .pickup-qr-reader img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
    }

    .pickup-qr-reader #qr-shaded-region {
        border-width: 24px !important;
    }
</style>
@endassets

@script
<script>
    const readerId = 'pickup-qr-reader';
    let scanner = null;
    let isScanning = false;
    let handlingScan = false;

    const startBtn = () => document.getElementById('pickup-start-camera');
    const stopBtn = () => document.getElementById('pickup-stop-camera');
    const alertBox = () => document.getElementById('pickup-camera-alert');

    const showCameraError = (message) => {
        const box = alertBox();
        if (! box) {
            return;
        }

        box.textContent = message;
        box.classList.remove('d-none');
    };

    const clearCameraError = () => {
        const box = alertBox();
        if (! box) {
            return;
        }

        box.textContent = '';
        box.classList.add('d-none');
    };

    const setScanningUi = (active) => {
        isScanning = active;
        const start = startBtn();
        const stop = stopBtn();

        if (start) {
            start.disabled = active;
        }

        if (stop) {
            stop.disabled = ! active;
        }
    };

    const resolveCameraErrorMessage = (error) => {
        const box = alertBox();
        const name = error?.name || '';
        const message = String(error?.message || error || '').toLowerCase();

        if (! window.isSecureContext) {
            return box?.dataset.insecureMessage
                || 'Camera requires HTTPS. Open this page with https:// and try again.';
        }

        if (
            name === 'NotAllowedError'
            || name === 'PermissionDeniedError'
            || message.includes('permission')
            || message.includes('not allowed')
            || message.includes('denied')
        ) {
            return box?.dataset.deniedMessage
                || 'Camera access denied. Allow permission, then try again.';
        }

        if (
            name === 'NotFoundError'
            || name === 'DevicesNotFoundError'
            || message.includes('requested device not found')
            || message.includes('no camera')
        ) {
            return box?.dataset.notFoundMessage
                || 'No camera was found on this device.';
        }

        return box?.dataset.failedMessage
            || 'Could not start the camera. Check browser permissions, then try again.';
    };

    const stopCamera = async () => {
        if (! scanner) {
            setScanningUi(false);
            return;
        }

        try {
            if (isScanning) {
                await scanner.stop();
            }
        } catch (error) {
            // Camera may already be stopped.
        }

        try {
            await scanner.clear();
        } catch (error) {
            // Ignore clear errors when reader was already torn down.
        }

        scanner = null;
        setScanningUi(false);
    };

    const requestCameraPermission = async () => {
        if (! navigator.mediaDevices?.getUserMedia) {
            throw new Error('getUserMedia is not supported in this browser.');
        }

        // Explicit permission prompt so the browser shows Allow / Block.
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: { facingMode: { ideal: 'environment' } },
        });

        stream.getTracks().forEach((track) => track.stop());
    };

    const resolveCameraConfig = async () => {
        try {
            const cameras = await Html5Qrcode.getCameras();

            if (Array.isArray(cameras) && cameras.length > 0) {
                const rear = cameras.find((camera) => {
                    const label = String(camera.label || '').toLowerCase();

                    return label.includes('back')
                        || label.includes('rear')
                        || label.includes('environment');
                });

                return rear?.id || cameras[cameras.length - 1].id;
            }
        } catch (error) {
            // Fall through to facingMode constraints.
        }

        return { facingMode: 'environment' };
    };

    const startScannerWithConfig = async (cameraConfig) => {
        await scanner.start(
            cameraConfig,
            {
                fps: 10,
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const edge = Math.min(viewfinderWidth, viewfinderHeight);
                    const size = Math.max(140, Math.floor(edge * 0.65));

                    return { width: size, height: size };
                },
                aspectRatio: 1.333,
            },
            async (decodedText) => {
                if (handlingScan) {
                    return;
                }

                handlingScan = true;

                try {
                    await stopCamera();
                    await $wire.scanPickupQr(decodedText);
                } finally {
                    handlingScan = false;
                }
            },
            () => {
                // Keep scanning until a QR is found.
            }
        );
    };

    const startCamera = async () => {
        clearCameraError();

        if (typeof Html5Qrcode === 'undefined') {
            showCameraError('QR scanner library failed to load.');
            return;
        }

        if (! document.getElementById(readerId)) {
            return;
        }

        if (! window.isSecureContext) {
            showCameraError(resolveCameraErrorMessage({ name: 'SecurityError' }));
            return;
        }

        await stopCamera();
        scanner = new Html5Qrcode(readerId);

        try {
            await requestCameraPermission();

            const preferredConfig = await resolveCameraConfig();

            try {
                await startScannerWithConfig(preferredConfig);
            } catch (primaryError) {
                // Desktop / single-camera devices often fail with environment-only constraints.
                const alreadyTriedUserFacing = typeof preferredConfig === 'object'
                    && preferredConfig?.facingMode === 'user';

                if (! alreadyTriedUserFacing) {
                    try {
                        await startScannerWithConfig({ facingMode: 'user' });
                    } catch (fallbackError) {
                        throw primaryError;
                    }
                } else {
                    throw primaryError;
                }
            }

            setScanningUi(true);
        } catch (error) {
            await stopCamera();
            showCameraError(resolveCameraErrorMessage(error));
        }
    };

    startBtn()?.addEventListener('click', () => {
        startCamera();
    });

    stopBtn()?.addEventListener('click', () => {
        stopCamera();
    });

    $wire.on('pickup-qr-scanned', () => {
        stopCamera();
    });

    document.addEventListener('livewire:navigating', () => {
        stopCamera();
    });
</script>
@endscript
