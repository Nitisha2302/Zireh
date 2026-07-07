<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\WarehousePanelService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Warehouse Order'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public string $statusCode = '';

    public function mount(CustomerOrder $order, WarehousePanelService $warehousePanelService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $warehousePanelService->ensureTajikistanOrderAccessible($admin, $order);

        $this->order = $order->load(['user', 'items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod']);
        $this->statusCode = $order->status;
    }

    public function updateStatus(WarehousePanelService $warehousePanelService): void
    {
        $this->validate([
            'statusCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->order = $warehousePanelService->updateOrderStatus($this->order, $this->statusCode)
                ->load(['user', 'items', 'orderStatus', 'warehouse', 'userAddress', 'shippingMethod']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.order_status_changed'));
    }

    public function render(OrderStatusService $orderStatusService)
    {
        return view('livewire.warehouse.tajikistan.order-detail-page', [
            'statusOptions' => $orderStatusService->listActive(),
        ])->title(__('admin.tajikistan_warehouse_order').' #'.$this->order->id);
    }
}
