<?php

namespace App\Livewire\Admin\Order;

use App\Models\CustomerOrder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Order Details'])]
class OrderDetailPage extends Component
{
    public CustomerOrder $order;

    public function mount(CustomerOrder $order): void
    {
        $this->order = $order->load(['user', 'items', 'commissionSlab']);
    }

    public function render()
    {
        return view('livewire.admin.order.order-detail-page')
            ->title('Order #'.$this->order->id);
    }
}
