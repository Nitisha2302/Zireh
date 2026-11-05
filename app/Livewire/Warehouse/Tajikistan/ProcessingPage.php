<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderException;
use App\Models\OrderStatusHistory;
use App\Models\ShippingMethod;
use App\Services\Order\OrderPickupService;
use App\Services\Warehouse\OrderExceptionService;
use App\Services\Warehouse\ParcelScannerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Warehouse Processing'])]
class ProcessingPage extends Component
{
    public string $scanInput = '';

    public ?CustomerOrder $order = null;

    public string $measurementMode = 'weight';

    public ?int $shippingMethodId = null;

    public ?string $packageWeightG = null;

    public ?string $packageLengthCm = null;

    public ?string $packageWidthCm = null;

    public ?string $packageHeightCm = null;

    public ?array $shippingPreview = null;

    /** @var list<array<string, mixed>> */
    public array $processedHistory = [];

    public ?string $lastUnidentifiedCode = null;

    public function scanIncomingParcel(
        ParcelScannerService $scannerService,
        OrderPickupService $pickupService,
    ): void {
        $code = trim($this->scanInput);
        $this->scanInput = '';

        if ($code === '') {
            return;
        }

        $this->loadOrderFromScan($code, $scannerService, $pickupService);
    }

    public function scanFromCamera(
        string $decodedText,
        ParcelScannerService $scannerService,
        OrderPickupService $pickupService,
    ): void {
        $code = trim($decodedText);
        if ($code === '') {
            return;
        }

        $this->loadOrderFromScan($code, $scannerService, $pickupService);
    }

    protected function loadOrderFromScan(
        string $rawPayload,
        ParcelScannerService $scannerService,
        OrderPickupService $pickupService,
    ): void {
        $this->resetErrorBag();
        $this->shippingPreview = null;
        $this->lastUnidentifiedCode = null;

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $resolved = $scannerService->findOrderByScan($rawPayload, $admin, 'tajikistan');
            /** @var CustomerOrder $foundOrder */
            $foundOrder = $resolved['order'];

            $this->order = $pickupService->prepareScannedOrderForProcessing(
                $foundOrder,
                $admin,
                $resolved['scanned_code']
            );

            $defaultMethod = $this->order->shipping_method_id
                ?: ShippingMethod::query()->where('is_active', true)->orderBy('id')->value('id');

            $this->shippingMethodId = $defaultMethod ? (int) $defaultMethod : null;
            $this->packageWeightG = $this->order->packageWeightGrams() !== null
                ? (string) $this->order->packageWeightGrams()
                : null;
            $this->packageLengthCm = $this->order->package_length_cm !== null
                ? (string) $this->order->package_length_cm
                : null;
            $this->packageWidthCm = $this->order->package_width_cm !== null
                ? (string) $this->order->package_width_cm
                : null;
            $this->packageHeightCm = $this->order->package_height_cm !== null
                ? (string) $this->order->package_height_cm
                : null;

            $this->recalculateShippingAutomatically($pickupService);
        } catch (ValidationException $exception) {
            $this->order = null;
            $this->lastUnidentifiedCode = $scannerService->normalizePayload($rawPayload) ?: $rawPayload;
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function updatedMeasurementMode(OrderPickupService $pickupService): void
    {
        if ($this->measurementMode === 'weight') {
            $this->packageLengthCm = null;
            $this->packageWidthCm = null;
            $this->packageHeightCm = null;
        } elseif ($this->measurementMode === 'cm') {
            $this->packageWeightG = null;
        }

        $this->recalculateShippingAutomatically($pickupService);
    }

    public function updatedShippingMethodId(OrderPickupService $pickupService): void
    {
        $this->recalculateShippingAutomatically($pickupService);
    }

    public function updatedPackageWeightG(OrderPickupService $pickupService): void
    {
        $this->recalculateShippingAutomatically($pickupService);
    }

    public function updatedPackageLengthCm(OrderPickupService $pickupService): void
    {
        $this->recalculateShippingAutomatically($pickupService);
    }

    public function updatedPackageWidthCm(OrderPickupService $pickupService): void
    {
        $this->recalculateShippingAutomatically($pickupService);
    }

    public function updatedPackageHeightCm(OrderPickupService $pickupService): void
    {
        $this->recalculateShippingAutomatically($pickupService);
    }

    public function recalculateShippingAutomatically(OrderPickupService $pickupService): void
    {
        if (! $this->order || $this->order->isReadyForPickup() || ! $this->hasCompleteMeasurements()) {
            $this->shippingPreview = null;

            return;
        }

        $this->resetErrorBag();

        try {
            $order = $this->orderWithSelectedShippingMethod();
            $this->shippingPreview = $pickupService->previewShipping($order, $this->measurementPayload());
        } catch (ValidationException $exception) {
            $this->shippingPreview = null;
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function completeProcessing(OrderPickupService $pickupService): void
    {
        if (! $this->order) {
            return;
        }

        $this->validate($this->measurementRules());

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $order = $this->syncSelectedShippingMethod();

            $completedOrder = $pickupService->confirmPickup(
                $order,
                $admin,
                $this->measurementPayload(),
                OrderStatusHistory::SOURCE_BARCODE_SCAN
            );

            array_unshift($this->processedHistory, [
                'order_id' => $completedOrder->id,
                'parcel_code' => $completedOrder->parcel_code,
                'elim_order_id' => $completedOrder->elim_order_id,
                'customer_name' => $completedOrder->user?->name ?? '—',
                'weight_g' => $completedOrder->packageWeightGrams(),
                'dimensions_cm' => $completedOrder->package_length_cm
                    ? sprintf('%s×%s×%s cm', $completedOrder->package_length_cm, $completedOrder->package_width_cm, $completedOrder->package_height_cm)
                    : '—',
                'shipping_fee_tjs' => (float) $completedOrder->pickup_shipping_fee_tjs,
                'pickup_token' => $completedOrder->pickup_token,
                'status_name' => $completedOrder->orderStatus?->name ?? $completedOrder->status,
                'processed_at' => now()->format('H:i:s'),
            ]);
            $this->processedHistory = array_slice($this->processedHistory, 0, 50);

            $this->order = null;
            $this->shippingPreview = null;
            $this->packageWeightG = null;
            $this->packageLengthCm = null;
            $this->packageWidthCm = null;
            $this->packageHeightCm = null;

            flash()->success(__('admin.tajikistan_processing_completed', ['id' => $completedOrder->id]));
            $this->dispatch('processing-scanner-refocus');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function flagException(string $type, OrderExceptionService $exceptionService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $code = $this->lastUnidentifiedCode
            ?? $this->order?->parcel_code
            ?? $this->order?->elim_order_id;

        $exception = $exceptionService->reportException([
            'customer_order_id' => $this->order?->id,
            'warehouse_id' => $admin->warehouse_id,
            'type' => $type,
            'origin_panel' => 'tajikistan',
            'scanned_code' => $code,
            'summary' => __('admin.exception_type_'.$type).($code ? ' ('.$code.')' : ''),
        ], $admin);

        $this->lastUnidentifiedCode = null;
        $this->order = null;
        $this->shippingPreview = null;

        flash()->warning(__('admin.exception_logged_success', ['id' => $exception->id]));
        $this->dispatch('processing-scanner-refocus');
    }

    protected function hasCompleteMeasurements(): bool
    {
        if (! $this->shippingMethodId) {
            return false;
        }

        if ($this->measurementMode === 'weight') {
            return is_numeric($this->packageWeightG) && (float) $this->packageWeightG > 0;
        }

        if ($this->measurementMode === 'cm') {
            return is_numeric($this->packageLengthCm) && (float) $this->packageLengthCm > 0
                && is_numeric($this->packageWidthCm) && (float) $this->packageWidthCm > 0
                && is_numeric($this->packageHeightCm) && (float) $this->packageHeightCm > 0;
        }

        return is_numeric($this->packageWeightG) && (float) $this->packageWeightG > 0
            && is_numeric($this->packageLengthCm) && (float) $this->packageLengthCm > 0
            && is_numeric($this->packageWidthCm) && (float) $this->packageWidthCm > 0
            && is_numeric($this->packageHeightCm) && (float) $this->packageHeightCm > 0;
    }

    /**
     * In-memory copy of the order using the currently selected shipping method.
     * Used for live previews so nothing is persisted until processing is completed.
     */
    protected function orderWithSelectedShippingMethod(): CustomerOrder
    {
        if (! $this->shippingMethodId || (int) $this->order->shipping_method_id === (int) $this->shippingMethodId) {
            return $this->order;
        }

        $previewOrder = clone $this->order;
        $previewOrder->setAttribute('shipping_method_id', (int) $this->shippingMethodId);
        $previewOrder->setRelation('shippingMethod', ShippingMethod::query()->find($this->shippingMethodId));

        return $previewOrder;
    }

    protected function syncSelectedShippingMethod(): CustomerOrder
    {
        if ($this->shippingMethodId && (int) $this->order->shipping_method_id !== (int) $this->shippingMethodId) {
            $this->order->update(['shipping_method_id' => $this->shippingMethodId]);
            $this->order = $this->order->fresh(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod', 'shipment']);
        }

        return $this->order;
    }

    /**
     * @return array<string, mixed>
     */
    protected function measurementRules(): array
    {
        $base = [
            'shippingMethodId' => ['required', 'integer', 'exists:shipping_methods,id'],
            'measurementMode' => ['required', 'string', 'in:weight,cm,both'],
        ];

        if ($this->measurementMode === 'cm') {
            return array_merge($base, [
                'packageLengthCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
                'packageWidthCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
                'packageHeightCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
            ]);
        }

        if ($this->measurementMode === 'both') {
            return array_merge($base, [
                'packageWeightG' => ['required', 'numeric', 'min:1', 'max:10000000'],
                'packageLengthCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
                'packageWidthCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
                'packageHeightCm' => ['required', 'numeric', 'min:0.1', 'max:500'],
            ]);
        }

        return array_merge($base, [
            'packageWeightG' => ['required', 'numeric', 'min:1', 'max:10000000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function measurementPayload(): array
    {
        if ($this->measurementMode === 'cm') {
            return [
                'mode' => 'cm',
                'package_weight_kg' => null,
                'package_length_cm' => (float) $this->packageLengthCm,
                'package_width_cm' => (float) $this->packageWidthCm,
                'package_height_cm' => (float) $this->packageHeightCm,
            ];
        }

        if ($this->measurementMode === 'both') {
            return [
                'mode' => 'both',
                'package_weight_kg' => round(((float) $this->packageWeightG) / 1000, 4),
                'package_length_cm' => (float) $this->packageLengthCm,
                'package_width_cm' => (float) $this->packageWidthCm,
                'package_height_cm' => (float) $this->packageHeightCm,
            ];
        }

        return [
            'mode' => 'weight',
            'package_weight_kg' => round(((float) $this->packageWeightG) / 1000, 4),
            'package_length_cm' => null,
            'package_width_cm' => null,
            'package_height_cm' => null,
        ];
    }

    public function render()
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.warehouse.tajikistan.processing-page', [
            'shippingMethods' => ShippingMethod::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(),
            'assignedWarehouse' => $admin->warehouse,
            'exceptionTypes' => [
                OrderException::TYPE_DAMAGED_PARCEL,
                OrderException::TYPE_MISSING_BARCODE,
                OrderException::TYPE_UNIDENTIFIED_SHIPMENT,
            ],
        ])->title(__('admin.tajikistan_warehouse_processing'));
    }
}
