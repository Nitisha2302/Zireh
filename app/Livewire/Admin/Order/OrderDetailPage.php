<?php

namespace App\Livewire\Admin\Order;

use App\Models\Admin;
use App\Models\CustomerOrder;
use App\Services\Order\CustomerOrderLifecycleService;
use App\Services\Order\OrderStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Order Details'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public string $statusCode = '';

    public function mount(CustomerOrder $order): void
    {
        $this->order = $order->load(['user', 'items', 'commissionSlab', 'orderStatus', 'warehouse', 'shippingMethod']);
        $this->statusCode = $order->status;
    }

    public function updateStatus(OrderStatusService $orderStatusService): void
    {
        $this->validate([
            'statusCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->order = $orderStatusService->updateOrderStatus($this->order, $this->statusCode)
                ->load(['user', 'items', 'commissionSlab', 'orderStatus', 'warehouse', 'shippingMethod']);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.order_status_changed'));
    }

    public function cancelOrder(CustomerOrderLifecycleService $lifecycleService): void
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $this->order = $lifecycleService->cancelByStaff($admin, $this->order);
            $this->statusCode = $this->order->status;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.order_cancelled_and_refunded'));
    }

    public function render(OrderStatusService $orderStatusService)
    {
        return view('livewire.admin.order.order-detail-page', [
            'statusOptions' => $orderStatusService->listActiveForManualUpdate(),
            'canCancelOrder' => $this->order->isCancellable(),
        ])->title('Order #'.$this->order->id);
    }
}
