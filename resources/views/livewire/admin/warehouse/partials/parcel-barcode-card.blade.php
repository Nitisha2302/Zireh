@php
    $parcelCode = $order->parcel_code ?: ('PCL-'.$order->id);
    $qrPayload = $order->parcelQrPayload();
    $barcodeSvg = \App\Services\Warehouse\ParcelScannerService::renderCode128Svg($parcelCode, 52, 2);
@endphp
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0">
                <i class="icon-base ti tabler-barcode me-1"></i>
                {{ __('admin.parcel_barcode_and_qr') }}
            </h5>
            <small class="text-body-secondary">{{ __('admin.parcel_barcode_and_qr_hint') }}</small>
        </div>
        <span class="badge bg-label-dark fs-6 font-monospace">{{ $parcelCode }}</span>
    </div>
    <div class="card-body">
        <div class="row g-4 align-items-center">
            <div class="col-md-7 text-center">
                <div class="border rounded p-3 bg-white">
                    <div class="mb-2">{!! $barcodeSvg !!}</div>
                    <div class="fw-bold font-monospace fs-6">{{ $parcelCode }}</div>
                    @if ($order->elim_order_id)
                        <div class="small text-body-secondary">{{ __('admin.elim_order_id') }}: {{ $order->elim_order_id }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-5 text-center">
                <div class="border rounded p-3 bg-light">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($qrPayload) }}"
                        alt="{{ $parcelCode }}"
                        width="140"
                        height="140"
                        class="img-fluid rounded bg-white p-2 border"
                    >
                    <div class="small text-body-secondary mt-2"><code>{{ $qrPayload }}</code></div>
                </div>
            </div>
        </div>
    </div>
</div>
