<?php

namespace App\Livewire\Admin\Order;

use App\Models\CustomerOrder;
use App\Services\Order\OrderStatusService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Orders'])]
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

    public function render(OrderStatusService $orderStatusService)
    {
        $orders = CustomerOrder::query()
            ->with(['user', 'items', 'orderStatus'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('elim_order_id', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('phone', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->platformFilter !== '', fn ($query) => $query->where('platform', $this->platformFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.order.order-list-page', [
            'orders' => $orders,
            'statusOptions' => $orderStatusService->listActive(),
        ])->title(__('admin.orders'));
    }
}
