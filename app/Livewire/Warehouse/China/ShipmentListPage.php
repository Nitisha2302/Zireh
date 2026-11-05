<?php

namespace App\Livewire\Warehouse\China;

use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\ShipmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::china-warehouse', ['title' => 'China Warehouse Shipments'])]
class ShipmentListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(ShipmentService $shipmentService, OrderStatusService $orderStatusService)
    {
        return view('livewire.warehouse.china.shipment-list-page', [
            'shipments' => $shipmentService->paginateChinaShipments(
                $this->search,
                $this->statusFilter,
            ),
            'statusOptions' => $orderStatusService->listActiveForManualUpdate(),
        ])->title(__('admin.shipment_management'));
    }
}
