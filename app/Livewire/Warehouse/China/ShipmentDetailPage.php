<?php

namespace App\Livewire\Warehouse\China;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Models\Shipment;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\ShipmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'Shipment Details'])]
class ShipmentDetailPage extends Component
{
    public Shipment $shipment;

    public string $statusCode = '';

    public string $orderToAdd = '';

    public function mount(Shipment $shipment, ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureChinaShipmentAccessible($admin, $shipment);

        $this->shipment = $shipment->load([
            'orderStatus',
            'createdByAdmin',
            'receivedByAdmin',
            'orders.user',
            'orders.orderStatus',
            'orders.warehouse',
        ]);
        $this->statusCode = $this->shipment->status;
    }

    public function updateStatus(ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureChinaShipmentAccessible($admin, $this->shipment);

        $this->validate([
            'statusCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->shipment = $shipmentService->updateShipmentStatus($this->shipment, $this->statusCode, $admin)
                ->load([
                    'orderStatus',
                    'createdByAdmin',
                    'receivedByAdmin',
                    'orders.user',
                    'orders.orderStatus',
                    'orders.warehouse',
                ]);
            $this->statusCode = $this->shipment->status;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.shipment_status_updated'));
    }

    public function addOrderToShipment(ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureChinaShipmentAccessible($admin, $this->shipment);

        if ($this->orderToAdd === '') {
            return;
        }

        try {
            $this->shipment = $shipmentService->attachOrders($this->shipment, [(int) $this->orderToAdd])
                ->load([
                    'orderStatus',
                    'createdByAdmin',
                    'receivedByAdmin',
                    'orders.user',
                    'orders.orderStatus',
                    'orders.warehouse',
                ]);
            $this->orderToAdd = '';
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.shipment_order_added'));
    }

    public function removeOrderFromShipment(int $orderId, ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureChinaShipmentAccessible($admin, $this->shipment);

        $order = CustomerOrder::query()->findOrFail($orderId);

        try {
            $this->shipment = $shipmentService->detachOrder($this->shipment, $order)
                ->load([
                    'orderStatus',
                    'createdByAdmin',
                    'receivedByAdmin',
                    'orders.user',
                    'orders.orderStatus',
                    'orders.warehouse',
                ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.shipment_order_removed'));
    }

    public function render(ShipmentService $shipmentService, OrderStatusService $orderStatusService)
    {
        return view('livewire.warehouse.china.shipment-detail-page', [
            'statusOptions' => $orderStatusService->listActiveForManualUpdate(),
            'unassignedOrders' => $this->shipment->isStatusUpdateLocked()
                ? collect()
                : $shipmentService->listUnassignedOrders(),
            'isLocked' => $this->shipment->isStatusUpdateLocked(),
        ])->title(__('admin.shipment').' '.$this->shipment->shipment_code);
    }
}
