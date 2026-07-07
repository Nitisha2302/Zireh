<?php

namespace App\Livewire\Warehouse\China;

use App\Services\Order\OrderStatusService;
use App\Services\Warehouse\WarehousePanelService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::china-warehouse', ['title' => 'China Warehouse Orders'])]
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
        return view('livewire.warehouse.china.order-list-page', [
            'orders' => $warehousePanelService->paginateChinaOrders(
                $this->search,
                $this->statusFilter,
                $this->platformFilter,
            ),
            'statusOptions' => $orderStatusService->listActive(),
        ])->title(__('admin.china_warehouse_orders'));
    }
}
