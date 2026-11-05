<?php

namespace App\Livewire\Admin\Order;

use App\Models\OrderStatusHistory;
use App\Services\Order\OrderStatusService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Order History & Audit Log'])]
class OrderAuditLogListPage extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $sourceFilter = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(OrderStatusService $orderStatusService)
    {
        $logs = OrderStatusHistory::query()
            ->with(['order.user', 'admin', 'fromOrderStatus', 'toOrderStatus'])
            ->when($this->search !== '', function (Builder $query): void {
                $search = $this->search;
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('order', function (Builder $orderQuery) use ($search): void {
                            $orderQuery->where('parcel_code', 'like', '%'.$search.'%')
                                ->orWhere('elim_order_id', 'like', '%'.$search.'%')
                                ->orWhere('parcel_tracking_id', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('admin', function (Builder $adminQuery) use ($search): void {
                            $adminQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($this->sourceFilter !== '', fn (Builder $query) => $query->where('source', $this->sourceFilter))
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('to_status', $this->statusFilter))
            ->latest('id')
            ->paginate(20);

        return view('livewire.admin.order.order-audit-log-list-page', [
            'logs' => $logs,
            'sources' => OrderStatusHistory::SOURCES,
            'statusOptions' => $orderStatusService->listActive(),
        ])->title(__('admin.order_audit_log'));
    }
}
