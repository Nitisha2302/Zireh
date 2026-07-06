<?php

namespace App\Livewire\Admin\ShippingRate;

use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingRateService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Shipping Rate'])]
class ShippingRateCreatePage extends Component
{
    public string $shippingMethodId = '';

    public string $minWeight = '';

    public string $maxWeight = '';

    public string $ratePerKg = '';

    public bool $isActive = true;

    protected function rules(): array
    {
        return [
            'shippingMethodId' => ['required', 'integer', 'exists:shipping_methods,id'],
            'minWeight' => ['required', 'numeric', 'min:0'],
            'maxWeight' => ['required', 'numeric', 'gt:minWeight'],
            'ratePerKg' => ['required', 'numeric', 'gt:0'],
            'isActive' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'shippingMethodId' => __('admin.shipping_method'),
            'minWeight' => __('admin.shipping_min_weight'),
            'maxWeight' => __('admin.shipping_max_weight'),
            'ratePerKg' => __('admin.shipping_rate_per_kg'),
        ];
    }

    public function save(ShippingRateService $service): void
    {
        $validated = $this->validate();

        try {
            $service->create([
                'shipping_method_id' => (int) $validated['shippingMethodId'],
                'min_weight' => $validated['minWeight'],
                'max_weight' => $validated['maxWeight'],
                'rate_per_kg' => $validated['ratePerKg'],
                'is_active' => $validated['isActive'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        flash()->success(__('admin.shipping_rate_created'));
        $this->redirectRoute('admin.shipping-rates.index');
    }

    public function render()
    {
        return view('livewire.admin.shipping-rate.shipping-rate-create-page', [
            'methods' => ShippingMethod::query()->orderBy('name')->get(),
        ])->title(__('admin.add_shipping_rate'));
    }
}
