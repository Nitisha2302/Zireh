<?php

namespace App\Livewire\Admin\OrderStatus;

use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Order Statuses'])]
class OrderStatusListPage extends Component
{
    use WithPagination;

    public string $view = 'active';

    public string $search = '';

    public string $statusFilter = '';

    public ?int $deleteId = null;

    public ?int $forceDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingView(): void
    {
        $this->resetPage();
        $this->search = '';
        $this->statusFilter = '';
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id, OrderStatusService $service): void
    {
        $service->toggleActive(OrderStatus::findOrFail($id));
        flash()->success(__('admin.order_status_active_updated'));
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info(__('admin.order_status_delete_confirm'));
    }

    public function confirmForceDelete(int $id): void
    {
        $this->forceDeleteId = $id;
        sweetalert()->showDenyButton()->warning(__('admin.order_status_force_delete_confirm'));
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(OrderStatusService $service): void
    {
        if ($this->forceDeleteId) {
            try {
                $service->forceDelete($this->forceDeleteId);
                flash()->success(__('admin.order_status_force_deleted'));
            } catch (\Illuminate\Validation\ValidationException $exception) {
                flash()->error($exception->validator->errors()->first('status'));
            }

            $this->forceDeleteId = null;

            return;
        }

        if (! $this->deleteId) {
            return;
        }

        try {
            $service->softDelete(OrderStatus::findOrFail($this->deleteId));
            flash()->success(__('admin.order_status_deleted'));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            flash()->error($exception->validator->errors()->first('status'));
        }

        $this->deleteId = null;
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        $this->forceDeleteId = null;
        flash()->info(__('admin.order_status_delete_cancelled'));
    }

    public function restore(int $id, OrderStatusService $service): void
    {
        $service->restore($id);
        flash()->success(__('admin.order_status_restored'));
    }

    public function render(OrderStatusService $service)
    {
        return view('livewire.admin.order-status.order-status-list-page', [
            'statuses' => $service->paginate(
                $this->view,
                $this->search ?: null,
                $this->statusFilter ?: null
            ),
            'colors' => OrderStatus::COLOR_OPTIONS,
        ])->title(__('admin.order_statuses'));
    }
}
