<?php

namespace App\Livewire\Warehouse\China;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\WarehousePanelService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'China Warehouse Order'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public string $statusCode = '';

    public string $parcelTrackingId = '';

    public function mount(CustomerOrder $order, WarehousePanelService $warehousePanelService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $warehousePanelService->ensureChinaOrderAccessible($admin, $order);

        $this->order = $order->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
        $this->statusCode = $order->status;
        $this->parcelTrackingId = (string) ($order->parcel_tracking_id ?? '');
    }

    public function updateStatus(WarehousePanelService $warehousePanelService): void
    {
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

    public function updateParcelTracking(WarehousePanelService $warehousePanelService): void
    {
        $this->validate([
            'parcelTrackingId' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->order = $warehousePanelService->updateParcelTracking($this->order, $this->parcelTrackingId)
                ->load(['user', 'items', 'orderStatus', 'warehouse', 'shippingMethod']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->parcelTrackingId = (string) ($this->order->parcel_tracking_id ?? '');

        flash()->success(__('admin.parcel_tracking_updated'));
    }

    public function render(OrderStatusService $orderStatusService)
    {
        return view('livewire.warehouse.china.order-detail-page', [
            'statusOptions' => $orderStatusService->listActive(),
        ])->title(__('admin.china_warehouse_order').' #'.$this->order->id);
    }
}
