<?php

namespace App\Livewire\Admin\ShippingRate;

use App\Livewire\Admin\ShippingRate\Concerns\ManagesShippingRateForm;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\Warehouse;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Edit Shipping Rate'])]
class ShippingRateEditPage extends Component
{
    use ManagesShippingRateForm;

    public ShippingRate $shippingRate;

    public string $shippingMethodId = '';

    public string $warehouseId = '';

    public string $minWeight = '';

    public string $maxWeight = '';

    public string $ratePerKg = '';

    public bool $isActive = true;

    public function mount(ShippingRate $shippingRate): void
    {
        $this->shippingRate = $shippingRate->load(['shippingMethod', 'warehouse']);
        $this->shippingMethodId = (string) $shippingRate->shipping_method_id;
        $this->warehouseId = $shippingRate->warehouse_id !== null ? (string) $shippingRate->warehouse_id : '';
        $this->mode = $shippingRate->billingMode();
        $this->minWeight = $this->mode === ShippingRate::MODE_WEIGHT
            ? (string) $shippingRate->minWeightGrams()
            : (string) $shippingRate->min_weight;
        $this->maxWeight = $this->mode === ShippingRate::MODE_WEIGHT
            ? (string) $shippingRate->maxWeightGrams()
            : (string) $shippingRate->max_weight;
        $this->ratePerKg = (string) $shippingRate->rate_per_kg;
        $this->isActive = $shippingRate->is_active;
    }

    protected function rules(): array
    {
        return $this->shippingRateRules();
    }

    protected function validationAttributes(): array
    {
        return $this->shippingRateValidationAttributes();
    }

    public function update(ShippingRateService $service): void
    {
        $validated = $this->validate();
        [$minWeight, $maxWeight] = $this->storedBoundsFromForm($validated);

        try {
            $service->update($this->shippingRate, [
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

        flash()->success(__('admin.shipping_rate_updated'));
        $this->redirectRoute('admin.shipping-rates.index');
    }

    public function render()
    {
        return view('livewire.admin.shipping-rate.shipping-rate-edit-page', [
            'methods' => ShippingMethod::query()->orderBy('name')->get(),
            'warehouses' => Warehouse::query()
                ->where(function ($query): void {
                    $query->where('status', Warehouse::STATUS_ACTIVE);
                    if ($this->shippingRate->warehouse_id) {
                        $query->orWhere('id', $this->shippingRate->warehouse_id);
                    }
                })
                ->orderBy('warehouse_name')
                ->get(),
        ])->title(__('admin.edit_shipping_rate'));
    }
}
