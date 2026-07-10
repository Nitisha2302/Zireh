<?php

namespace App\Livewire\Admin\OrderStatus;

use App\Models\OrderStatus;
use App\Services\Order\OrderStatusService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Order Status'])]
class OrderStatusCreatePage extends Component
{
    public array $name = [
        'en' => '',
        'ru' => '',
        'tg' => '',
    ];

    public string $code = '';

    public string $color = 'secondary';

    public string $description = '';

    public string $sortOrder = '100';

    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'name.en' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.tg' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:order_statuses,code'],
            'color' => ['required', 'string', 'in:'.implode(',', OrderStatus::COLOR_OPTIONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ];
    }

    public function save(OrderStatusService $service): void
    {
        $validated = $this->validate();

        try {
            $service->create([
                'name' => $validated['name'],
                'code' => strtolower($validated['code']),
                'color' => $validated['color'],
                'description' => $validated['description'] ?: null,
                'sort_order' => (int) $validated['sortOrder'],
                'is_active' => $validated['isActive'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        flash()->success(__('admin.order_status_created'));
        $this->redirectRoute('admin.order-statuses.index');
    }

    public function render()
    {
        return view('livewire.admin.order-status.order-status-create-page', [
            'colors' => OrderStatus::COLOR_OPTIONS,
        ])->title(__('admin.add_order_status'));
    }
}
