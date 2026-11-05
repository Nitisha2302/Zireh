<?php

namespace App\Livewire\Warehouse\China;

use App\Livewire\Concerns\InteractsWithExceptionManagement;
use App\Models\Admin;
use App\Models\OrderException;
use App\Services\Warehouse\OrderExceptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::china-warehouse', ['title' => 'China Warehouse Exceptions'])]
class ExceptionListPage extends Component
{
    use InteractsWithExceptionManagement;

    protected function exceptionPanelName(): string
    {
        return 'china';
    }

    public function render(OrderExceptionService $exceptionService)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.warehouse.china.exception-list-page', [
            'exceptions' => $exceptionService->paginateForPanel(
                $admin,
                'china',
                $this->search ?: null,
                $this->typeFilter ?: null,
                $this->statusFilter ?: null
            ),
            'types' => OrderException::TYPES,
            'statuses' => OrderException::STATUSES,
            'orderShowRoute' => 'china.orders.show',
        ])->title(__('admin.exception_management'));
    }
}
