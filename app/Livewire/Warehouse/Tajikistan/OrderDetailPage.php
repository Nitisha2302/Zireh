<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\OrderStatus;
use App\Models\ShippingMethod;
use App\Services\Order\OrderPickupService;
use App\Services\Warehouse\WarehousePanelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Warehouse Order'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public string $statusCode = '';

    public ?string $packageLengthCm = null;

    public ?string $packageWidthCm = null;

    public ?string $packageHeightCm = null;

    public ?string $packageWeightKg = null;

    public ?array $shippingPreview = null;

    public ?int $shippingMethodId = null;

    public function mount(CustomerOrder $order, WarehousePanelService $warehousePanelService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);

        $this->order = $order->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
        $this->statusCode = $order->status;
        $this->shippingMethodId = $order->shipping_method_id;
    }

    public function updatedPackageLengthCm(): void
    {
        $this->shippingPreview = null;
    }

    public function updatedPackageWidthCm(): void
    {
        $this->shippingPreview = null;
    }

    public function updatedPackageHeightCm(): void
    {
        $this->shippingPreview = null;
    }

    public function updatedPackageWeightKg(): void
    {
        $this->shippingPreview = null;
    }

    public function updatedShippingMethodId(): void
    {
        $this->shippingPreview = null;
    }

    public function previewPickupShipping(OrderPickupService $pickupService): void
    {
        $data = $this->validate($this->measurementRules());

        try {
            $order = $this->prepareOrderForPickup();
            $this->shippingPreview = $pickupService->previewShipping($order, [
                'package_length_cm' => (float) $data['packageLengthCm'],
                'package_width_cm' => (float) $data['packageWidthCm'],
                'package_height_cm' => (float) $data['packageHeightCm'],
                'package_weight_kg' => (float) $data['packageWeightKg'],
            ]);
        } catch (ValidationException $exception) {
            $this->shippingPreview = null;
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function confirmPickup(OrderPickupService $pickupService): void
    {
        $data = $this->validate($this->measurementRules());

        try {
            /** @var Admin $admin */
            $admin = Auth::guard('admin')->user();
            $order = $this->prepareOrderForPickup();

            $this->order = $pickupService->confirmPickup($order, $admin, [
                'package_length_cm' => (float) $data['packageLengthCm'],
                'package_width_cm' => (float) $data['packageWidthCm'],
                'package_height_cm' => (float) $data['packageHeightCm'],
                'package_weight_kg' => (float) $data['packageWeightKg'],
            ]);

            $this->shippingPreview = null;
            $this->statusCode = $this->order->status;
            $this->shippingMethodId = $this->order->shipping_method_id;
            flash()->success(__('admin.pickup_confirmed'));
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
        }
    }

    public function updateStatus(WarehousePanelService $warehousePanelService): void
    {
        if ($this->order->isReadyForPickup()) {
            return;
        }

        $this->validate([
            'statusCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->order = $warehousePanelService->updateOrderStatus($this->order, $this->statusCode)
                ->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.order_status_changed'));
    }

    public function render()
    {
        return view('livewire.warehouse.tajikistan.order-detail-page', [
            'canMeasure' => in_array($this->order->status, CustomerOrder::PRE_PICKUP_STATUSES, true),
            'isReadyForPickup' => $this->order->isReadyForPickup(),
            'shippingMethods' => ShippingMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ])->title(__('admin.tajikistan_warehouse_order').' #'.$this->order->id);
    }

    protected function measurementRules(): array
    {
        return [
            'shippingMethodId' => ['required', 'integer', 'exists:shipping_methods,id'],
            'packageLengthCm' => ['required', 'numeric', 'gt:0'],
            'packageWidthCm' => ['required', 'numeric', 'gt:0'],
            'packageHeightCm' => ['required', 'numeric', 'gt:0'],
            'packageWeightKg' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function prepareOrderForPickup(): CustomerOrder
    {
        $method = ShippingMethod::query()
            ->whereKey($this->shippingMethodId)
            ->where('is_active', true)
            ->first();

        if (! $method) {
            throw ValidationException::withMessages([
                'shippingMethodId' => [__('admin.shipping_method_not_available')],
            ]);
        }

        if ((int) $this->order->shipping_method_id !== (int) $method->id) {
            $this->order->update(['shipping_method_id' => $method->id]);
            $this->order = $this->order->fresh(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
        }

        return $this->order;
    }
}
