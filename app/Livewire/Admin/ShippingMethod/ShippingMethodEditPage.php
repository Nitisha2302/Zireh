<?php

namespace App\Livewire\Admin\ShippingMethod;

use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingMethodService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Edit Shipping Method'])]
class ShippingMethodEditPage extends Component
{
    public ShippingMethod $shippingMethod;

    public string $name = '';

    public string $code = '';

    public string $volumetricDivisor = '';

    public string $minimumCharge = '';

    public bool $isActive = true;

    public function mount(ShippingMethod $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
        $this->name = $shippingMethod->name;
        $this->code = $shippingMethod->code;
        $this->volumetricDivisor = (string) $shippingMethod->volumetric_divisor;
        $this->minimumCharge = (string) $shippingMethod->minimum_charge;
        $this->isActive = $shippingMethod->is_active;
    }

    protected function rules(): array
    {
        return $this->methodRules($this->shippingMethod->id);
    }

    public function update(ShippingMethodService $service): void
    {
        $validated = $this->validate();

        try {
            $service->update($this->shippingMethod, $this->mapValidated($validated));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());
            throw $exception;
        }

        flash()->success(__('admin.shipping_method_updated'));
        $this->redirectRoute('admin.shipping-methods.index');
    }

    public function render()
    {
        return view('livewire.admin.shipping-method.shipping-method-edit-page')
            ->title(__('admin.edit_shipping_method'));
    }

    protected function methodRules(?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('shipping_methods', 'code');

        if ($ignoreId) {
            $codeRule = $codeRule->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9\-_]+$/', $codeRule],
            'volumetricDivisor' => ['required', 'integer', 'min:1'],
            'minimumCharge' => ['required', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ];
    }

    protected function mapValidated(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'code' => strtolower($validated['code']),
            'volumetric_divisor' => (int) $validated['volumetricDivisor'],
            'minimum_charge' => $validated['minimumCharge'],
            'is_active' => $validated['isActive'],
        ];
    }
}
