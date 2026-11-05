<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\ShipmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Warehouse Shipments'])]
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
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.warehouse.tajikistan.shipment-list-page', [
            'shipments' => $shipmentService->paginateTajikistanShipments(
                $admin,
                $this->search,
                $this->statusFilter,
            ),
            'statusOptions' => $orderStatusService->listActiveForManualUpdate(),
            'assignedWarehouse' => $admin->warehouse,
        ])->title(__('admin.tajikistan_warehouse_shipments'));
    }
}
