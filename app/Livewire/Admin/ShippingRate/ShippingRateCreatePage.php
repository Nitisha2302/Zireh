<?php

namespace App\Livewire\Admin\ShippingRate;

use App\Livewire\Admin\ShippingRate\Concerns\ManagesShippingRateForm;
use App\Models\ShippingMethod;
use App\Models\Warehouse;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Shipping Rate'])]
class ShippingRateCreatePage extends Component
{
    use ManagesShippingRateForm;

    public string $shippingMethodId = '';

    public string $warehouseId = '';

    public string $minWeight = '';

    public string $maxWeight = '';

    public string $ratePerKg = '';

    public bool $isActive = true;

    protected function rules(): array
    {
        return $this->shippingRateRules();
    }

    protected function validationAttributes(): array
    {
        return $this->shippingRateValidationAttributes();
    }

    public function save(ShippingRateService $service): void
    {
        $validated = $this->validate();
        [$minWeight, $maxWeight] = $this->storedBoundsFromForm($validated);

        try {
            $service->create([
                'shipping_method_id' => (int) $validated['shippingMethodId'],
                'warehouse_id' => (int) $validated['warehouseId'],
                'mode' => $validated['mode'],
                'min_weight' => $minWeight,
                'max_weight' => $maxWeight,
                'rate_per_kg' => $validated['ratePerKg'],
                'is_active' => $validated['isActive'],
            ]);
        } catch (ValidationException $exception) {
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
            'warehouses' => Warehouse::query()
                ->where('status', Warehouse::STATUS_ACTIVE)
                ->orderBy('warehouse_name')
                ->get(),
        ])->title(__('admin.add_shipping_rate'));
    }
}
