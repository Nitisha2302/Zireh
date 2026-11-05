<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Models\Shipment;
use App\Services\Warehouse\ShipmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Shipment Details'])]
class ShipmentDetailPage extends Component
{
    public Shipment $shipment;

    public function mount(Shipment $shipment, ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureTajikistanShipmentAccessible($admin, $shipment);

        $this->shipment = $shipment->load([
            'orderStatus',
            'createdByAdmin',
            'receivedByAdmin',
            'orders.user',
            'orders.orderStatus',
            'orders.warehouse',
        ]);
    }

    public function markReceived(ShipmentService $shipmentService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $shipmentService->ensureTajikistanShipmentAccessible($admin, $this->shipment);

        try {
            $this->shipment = $shipmentService->markReceivedInTajikistan($this->shipment, $admin)
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

        flash()->success(__('admin.shipment_received_in_tajikistan_success'));
    }

    public function render()
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $orders = $this->shipment->orders;
        if ($admin->isTajikistanWarehouseStaff()) {
            $orders = $orders->where('warehouse_id', $admin->warehouse_id)->values();
        }

        return view('livewire.warehouse.tajikistan.shipment-detail-page', [
            'orders' => $orders,
            'isLocked' => $this->shipment->isStatusUpdateLocked(),
            'assignedWarehouse' => $admin->warehouse,
        ])->title(__('admin.shipment').' '.$this->shipment->shipment_code);
    }
}
