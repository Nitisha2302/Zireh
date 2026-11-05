<?php

namespace App\Livewire\Admin\Order;

use App\Livewire\Concerns\InteractsWithExceptionManagement;
use App\Models\Admin;
use App\Models\OrderException;
use App\Services\Warehouse\OrderExceptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Order Exceptions'])]
class ExceptionListPage extends Component
{
    use InteractsWithExceptionManagement;

    protected function exceptionPanelName(): string
    {
        return 'admin';
    }

    public function render(OrderExceptionService $exceptionService)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.admin.order.exception-list-page', [
            'exceptions' => $exceptionService->paginateForPanel(
                $admin,
                'admin',
                $this->search ?: null,
                $this->typeFilter ?: null,
                $this->statusFilter ?: null
            ),
            'types' => OrderException::TYPES,
            'statuses' => OrderException::STATUSES,
            'orderShowRoute' => 'admin.orders.show',
        ])->title(__('admin.exception_management'));
    }
}
