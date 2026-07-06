<?php

namespace App\Livewire\Admin\Order;

use App\Models\CustomerOrder;
use App\Services\Order\OrderStatusService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Order Details'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public string $statusCode = '';

    public function mount(CustomerOrder $order): void
    {
        $this->order = $order->load(['user', 'items', 'commissionSlab', 'orderStatus']);
        $this->statusCode = $order->status;
    }

    public function updateStatus(OrderStatusService $orderStatusService): void
    {
        $this->validate([
            'statusCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->order = $orderStatusService->updateOrderStatus($this->order, $this->statusCode)
                ->load(['user', 'items', 'commissionSlab', 'orderStatus']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.order_status_changed'));
    }

    public function render(OrderStatusService $orderStatusService)
    {
        return view('livewire.admin.order.order-detail-page', [
            'statusOptions' => $orderStatusService->listActive(),
        ])->title('Order #'.$this->order->id);
    }
}
