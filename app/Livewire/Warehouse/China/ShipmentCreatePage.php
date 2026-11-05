<?php

namespace App\Livewire\Warehouse\China;

use App\Models\Admin;
use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\ShipmentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'Create Shipment'])]
class ShipmentCreatePage extends Component
{
    public string $shipmentCode = '';

    public string $statusCode = OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE;

    public string $notes = '';

    /** @var list<int|string> */
    public array $selectedOrderIds = [];

    public string $orderToAdd = '';

    public function addOrderFromDropdown(): void
    {
        if ($this->orderToAdd === '') {
            return;
        }

        $orderId = (int) $this->orderToAdd;
        $currentIds = array_map('intval', $this->selectedOrderIds);

        if ($orderId > 0 && ! in_array($orderId, $currentIds, true)) {
            $currentIds[] = $orderId;
            $this->selectedOrderIds = array_values(array_unique($currentIds));
        }

        $this->orderToAdd = '';
    }

    public function removeSelectedOrder(int $orderId): void
    {
        $this->selectedOrderIds = array_values(array_filter(
            array_map('intval', $this->selectedOrderIds),
            fn (int $id): bool => $id !== $orderId
        ));
    }

    public function save(ShipmentService $shipmentService)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin->canAccessChinaWarehousePanel()) {
            abort(403);
        }

        try {
            $shipment = $shipmentService->createShipment($admin, [
                'shipment_code' => $this->shipmentCode,
                'status' => $this->statusCode,
                'notes' => $this->notes,
                'order_ids' => $this->selectedOrderIds,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.shipment_created'));

        return $this->redirect(route('china.shipments.show', $shipment), navigate: true);
    }

    public function render(ShipmentService $shipmentService, OrderStatusService $orderStatusService)
    {
        $unassignedOrders = $shipmentService->listUnassignedOrders();
        $selectedIds = array_map('intval', $this->selectedOrderIds);

        return view('livewire.warehouse.china.shipment-create-page', [
            'unassignedOrders' => $unassignedOrders,
            'selectedOrders' => $unassignedOrders->whereIn('id', $selectedIds)->values(),
            'statusOptions' => $orderStatusService->listActiveForWarehouseUpdate(),
        ])->title(__('admin.create_shipment'));
    }
}
