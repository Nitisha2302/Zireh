<?php

namespace App\Livewire\Admin\ShippingMethod;

use App\Services\Shipping\ShippingMethodService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Add Shipping Method'])]
class ShippingMethodCreatePage extends Component
{
    public string $name = '';

    public string $code = '';

    public string $volumetricDivisor = '';

    public string $minimumCharge = '';

    public bool $isActive = true;

    protected function rules(): array
    {
        return $this->methodRules();
    }

    public function save(ShippingMethodService $service): void
    {
        $validated = $this->validate();
        $service->create($this->mapValidated($validated));

        flash()->success(__('admin.shipping_method_created'));
        $this->redirectRoute('admin.shipping-methods.index');
    }

    public function render()
    {
        return view('livewire.admin.shipping-method.shipping-method-create-page')
            ->title(__('admin.add_shipping_method'));
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
