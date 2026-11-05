<?php

namespace App\Livewire\Warehouse\Tajikistan;

use App\Livewire\Concerns\InteractsWithExceptionManagement;
use App\Models\Admin;
use App\Models\OrderException;
use App\Services\Warehouse\OrderExceptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::tajikistan-warehouse', ['title' => 'Tajikistan Warehouse Exceptions'])]
class ExceptionListPage extends Component
{
    use InteractsWithExceptionManagement;

    protected function exceptionPanelName(): string
    {
        return 'tajikistan';
    }

    public function render(OrderExceptionService $exceptionService)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        return view('livewire.warehouse.tajikistan.exception-list-page', [
            'exceptions' => $exceptionService->paginateForPanel(
                $admin,
                'tajikistan',
                $this->search ?: null,
                $this->typeFilter ?: null,
                $this->statusFilter ?: null
            ),
            'types' => OrderException::TYPES,
            'statuses' => OrderException::STATUSES,
            'orderShowRoute' => 'tajikistan.orders.show',
            'assignedWarehouse' => $admin->warehouse,
        ])->title(__('admin.exception_management'));
    }
}
