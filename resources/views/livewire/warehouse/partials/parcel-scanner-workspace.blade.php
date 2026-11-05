<div class="row g-4">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="icon-base ti tabler-barcode me-1"></i>
                    {{ __('admin.continuous_parcel_scanner') }}
                </h5>
                <span class="badge bg-label-success">
                    {{ collect($scanHistory)->where('success', true)->count() }} {{ __('admin.scanned_count') }}
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="target-status-select">
                        1. {{ __('admin.target_status_on_scan') }}
                    </label>
                    <select id="target-status-select" class="form-select" wire:model="targetStatusCode">
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-body-secondary d-block mt-1">
                        {{ __('admin.target_status_on_scan_hint') }}
                    </small>
                </div>

                <form wire:submit="scanFromInput" class="mb-3">
                    <label class="form-label fw-semibold" for="continuous-scan-input">
                        2. {{ __('admin.scan_barcode_or_qr_input') }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="icon-base ti tabler-scan"></i></span>
                        <input
                            id="continuous-scan-input"
                            type="text"
                            class="form-control @error('scanInput') is-invalid @enderror @error('statusCode') is-invalid @enderror"
                            wire:model="scanInput"
                            placeholder="{{ __('admin.scan_barcode_placeholder') }}"
                            autofocus
                            autocomplete="off"
                        >
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="scanFromInput">
                            {{ __('admin.scan_and_update') }}
                        </button>
                    </div>
                    <small class="text-body-secondary d-block mt-1">
                        {{ __('admin.continuous_scan_input_hint') }}
                    </small>
                    @error('scanInput') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                    @error('statusCode') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
                </form>

                @if ($lastUnidentifiedCode)
                    <div class="alert alert-warning mb-3">
                        <div class="fw-semibold mb-2">
                            <i class="icon-base ti tabler-alert-triangle me-1"></i>
                            {{ __('admin.unidentified_parcel_prompt', ['code' => $lastUnidentifiedCode]) }}
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($exceptionTypes as $exType)
                                <button
                                    type="button"
                                    class="btn btn-xs btn-warning"
                                    wire:click="flagUnidentifiedAsException('{{ $exType }}')"
                                >
                                    + {{ __('admin.exception_type_'.$exType) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small">3. {{ __('admin.mobile_camera_continuous_scan') }}</span>
                        <div class="d-flex gap-2">
                            <button type="button" id="parcel-start-camera" class="btn btn-sm btn-outline-primary">
                                <i class="icon-base ti tabler-camera me-1"></i> {{ __('admin.start_camera') }}
                            </button>
                            <button type="button" id="parcel-stop-camera" class="btn btn-sm btn-label-secondary" disabled>
                                <i class="icon-base ti tabler-camera-off me-1"></i> {{ __('admin.stop_camera') }}
                            </button>
                        </div>
                    </div>

                    <div
                        id="parcel-camera-alert"
                        class="alert alert-danger d-none py-2 small"
                        role="alert"
                    ></div>

                    <div wire:ignore class="d-flex justify-content-center">
                        <div id="parcel-qr-reader" class="parcel-qr-reader rounded border overflow-hidden bg-dark"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        @if ($lastScanResult)
            <div class="alert {{ ($lastScanResult['success'] ?? false) ? 'alert-success' : 'alert-danger' }} d-flex justify-content-between align-items-center mb-4">
                <div>
                    @if ($lastScanResult['success'] ?? false)
                        <div class="fw-bold">
                            <i class="icon-base ti tabler-circle-check me-1"></i>
                            #{{ $lastScanResult['order_id'] }} · {{ $lastScanResult['parcel_code'] }} ({{ $lastScanResult['elim_order_id'] ?? '—' }})
                        </div>
                        <div class="small">
                            {{ $lastScanResult['previous_status_name'] }} → <strong>{{ $lastScanResult['new_status_name'] }}</strong>
                            · {{ $lastScanResult['customer_name'] }}
                        </div>
                    @else
                        <div class="fw-bold">
                            <i class="icon-base ti tabler-alert-circle me-1"></i>
                            {{ $lastScanResult['scanned_code'] }}
                        </div>
                        <div class="small">{{ $lastScanResult['error_message'] }}</div>
                    @endif
                </div>
                <span class="badge bg-white text-dark">{{ $lastScanResult['scanned_at'] }}</span>
            </div>
        @endif

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('admin.continuous_scan_session_log') }} ({{ count($scanHistory) }})</h5>
                @if (count($scanHistory) > 0)
                    <button type="button" class="btn btn-sm btn-label-secondary" wire:click="clearHistory">
                        {{ __('admin.clear_scan_log') }}
                    </button>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin.time') }}</th>
                            <th>{{ __('admin.scanned_code') }}</th>
                            <th>{{ __('admin.orders') }}</th>
                            <th>{{ __('admin.customer') }}</th>
                            <th>{{ __('admin.status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($scanHistory as $entry)
                            <tr class="{{ ($entry['success'] ?? false) ? '' : 'table-danger' }}">
                                <td class="text-nowrap small text-body-secondary">{{ $entry['scanned_at'] }}</td>
                                <td><code>{{ $entry['scanned_code'] }}</code></td>
                                @if ($entry['success'] ?? false)
                                    <td>
                                        <div class="fw-semibold">#{{ $entry['order_id'] }} · {{ $entry['parcel_code'] }}</div>
                                        <small class="text-body-secondary">{{ $entry['elim_order_id'] ?? '—' }}</small>
                                    </td>
                                    <td>{{ $entry['customer_name'] }}</td>
                                    <td>
                                        <span class="text-body-secondary small">{{ $entry['previous_status_name'] }}</span>
                                        <i class="icon-base ti tabler-arrow-right mx-1 small"></i>
                                        <span class="badge {{ $entry['new_status_badge'] }}">{{ $entry['new_status_name'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route($orderShowRoute, $entry['order_id']) }}" class="btn btn-xs btn-label-primary">
                                            {{ __('admin.view') }}
                                        </a>
                                    </td>
                                @else
                                    <td colspan="3" class="text-danger small">
                                        {{ $entry['error_message'] }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route($exceptionsRoute) }}" class="btn btn-xs btn-label-warning">
                                            {{ __('admin.exception_management') }}
                                        </a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-body-secondary">
                                    {{ __('admin.no_scans_in_session') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@assets
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
    .parcel-qr-reader {
        width: 100%;
        max-width: 340px;
        min-height: 220px;
    }
    .parcel-qr-reader video,
    .parcel-qr-reader canvas {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover;
    }
</style>
@endassets

@script
<script>
    const readerId = 'parcel-qr-reader';
    let scanner = null;
    let isScanning = false;
    let lastCode = '';
    let lastScanTime = 0;

    const focusInput = () => {
        const input = document.getElementById('continuous-scan-input');
        if (input) {
            input.focus();
        }
    };

    $wire.on('scanner-refocus', () => {
        setTimeout(focusInput, 50);
    });

    const startBtn = () => document.getElementById('parcel-start-camera');
    const stopBtn = () => document.getElementById('parcel-stop-camera');
    const alertBox = () => document.getElementById('parcel-camera-alert');

    const setScanningUi = (active) => {
        isScanning = active;
        if (startBtn()) startBtn().disabled = active;
        if (stopBtn()) stopBtn().disabled = !active;
    };

    const stopCamera = async () => {
        if (!scanner) {
            setScanningUi(false);
            return;
        }
        try {
            if (isScanning) await scanner.stop();
        } catch (e) {}
        try {
            await scanner.clear();
        } catch (e) {}
        scanner = null;
        setScanningUi(false);
    };

    const startCamera = async () => {
        if (alertBox()) {
            alertBox().textContent = '';
            alertBox().classList.add('d-none');
        }
        if (typeof Html5Qrcode === 'undefined' || !document.getElementById(readerId)) {
            return;
        }

        await stopCamera();
        scanner = new Html5Qrcode(readerId);

        try {
            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 240, height: 160 },
                },
                async (decodedText) => {
                    const now = Date.now();
                    if (decodedText === lastCode && (now - lastScanTime) < 1800) {
                        return;
                    }
                    lastCode = decodedText;
                    lastScanTime = now;
                    await $wire.scanFromCamera(decodedText);
                },
                () => {}
            );
            setScanningUi(true);
        } catch (error) {
            await stopCamera();
            if (alertBox()) {
                alertBox().textContent = String(error?.message || error || 'Camera could not start.');
                alertBox().classList.remove('d-none');
            }
        }
    };

    startBtn()?.addEventListener('click', () => startCamera());
    stopBtn()?.addEventListener('click', () => stopCamera());

    document.addEventListener('livewire:navigating', () => {
        stopCamera();
    });
</script>
@endscript
