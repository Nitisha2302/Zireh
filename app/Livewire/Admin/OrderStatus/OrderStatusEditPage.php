<?php

namespace App\Livewire\Admin\OrderStatus;

use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Edit Order Status'])]
class OrderStatusEditPage extends Component
{
    public OrderStatus $orderStatus;

    public string $name = '';

    public string $code = '';

    public string $color = 'secondary';

    public string $description = '';

    public string $sortOrder = '0';

    public bool $isActive = true;

    public function mount(OrderStatus $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
        $this->name = $orderStatus->name;
        $this->code = $orderStatus->code;
        $this->color = $orderStatus->color;
        $this->description = $orderStatus->description ?? '';
        $this->sortOrder = (string) $orderStatus->sort_order;
        $this->isActive = $orderStatus->is_active;
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'in:'.implode(',', OrderStatus::COLOR_OPTIONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ];

        if (! $this->orderStatus->isSystem()) {
            $rules['code'] = ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:order_statuses,code,'.$this->orderStatus->id];
        }

        return $rules;
    }

    public function update(OrderStatusService $service): void
    {
        $validated = $this->validate();

        $payload = [
            'name' => $validated['name'],
            'color' => $validated['color'],
            'description' => $validated['description'] ?: null,
            'sort_order' => (int) $validated['sortOrder'],
            'is_active' => $validated['isActive'],
        ];

        if (! $this->orderStatus->isSystem()) {
            $payload['code'] = strtolower($validated['code']);
        }

        try {
            $service->update($this->orderStatus, $payload);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        flash()->success(__('admin.order_status_updated'));
        $this->redirectRoute('admin.order-statuses.index');
    }

    public function render()
    {
        return view('livewire.admin.order-status.order-status-edit-page', [
            'colors' => OrderStatus::COLOR_OPTIONS,
        ])->title(__('admin.edit_order_status'));
    }
}
