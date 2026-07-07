<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Models\Admin;
use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\WarehousePanelService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::warehouse', ['title' => 'Tajikistan Warehouse Orders', 'panel' => 'tajikistan'])]
class OrderListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $platformFilter = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPlatformFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(WarehousePanelService $warehousePanelService, OrderStatusService $orderStatusService)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.warehouse.tajikistan.order-list-page', [
            'orders' => $warehousePanelService->paginateTajikistanOrders(
                $admin,
                $this->search,
                $this->statusFilter,
                $this->platformFilter,
            ),
            'statusOptions' => $orderStatusService->listActive(),
            'assignedWarehouse' => $admin->warehouse,
        ])->title(__('admin.tajikistan_warehouse_orders'));
    }
}
