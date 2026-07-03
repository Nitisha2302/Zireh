<?php

namespace App\Livewire\Admin\PlatformCommissionSlab;

use App\Models\Platform;
use App\Models\PlatformCommissionSlab;
use App\Services\PlatformCommissionService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Create Commission Slab'])]
class PlatformCommissionSlabCreatePage extends Component
{
    public Platform $platform;

    public string $minAmount = '';

    public string $maxAmount = '';

    public bool $isUnlimited = false;

    public string $commissionPercentage = '';

    public bool $isActive = true;

    public function mount(Platform $platform): void
    {
        $this->platform = $platform;
    }

    protected function rules(): array
    {
        return [
            'minAmount' => ['required', 'numeric', 'min:0'],
            'maxAmount' => ['nullable', 'required_unless:isUnlimited,true', 'numeric', 'gt:minAmount'],
            'isUnlimited' => ['boolean'],
            'commissionPercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'isActive' => ['boolean'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'minAmount' => 'minimum amount',
            'maxAmount' => 'maximum amount',
            'commissionPercentage' => 'commission percentage',
            'isUnlimited' => 'unlimited maximum',
            'isActive' => 'status',
        ];
    }

    protected function messages(): array
    {
        return [
            'minAmount.required' => 'Minimum amount is required.',
            'minAmount.numeric' => 'Minimum amount must be a valid number.',
            'minAmount.min' => 'Minimum amount must be at least 0.',
            'maxAmount.required_unless' => 'Maximum amount is required unless unlimited is enabled.',
            'maxAmount.numeric' => 'Maximum amount must be a valid number.',
            'maxAmount.gt' => 'Maximum amount must be greater than minimum amount.',
            'commissionPercentage.required' => 'Commission percentage is required.',
            'commissionPercentage.numeric' => 'Commission percentage must be a valid number.',
            'commissionPercentage.min' => 'Commission percentage cannot be negative.',
            'commissionPercentage.max' => 'Commission percentage cannot exceed 100.',
        ];
    }

    public function updatedIsUnlimited(bool $value): void
    {
        if ($value) {
            $this->maxAmount = '';
            $this->resetErrorBag('maxAmount');
        }
    }

    public function save(PlatformCommissionService $commissionService): void
    {
        $validated = $this->validate();

        $minAmount = (float) $validated['minAmount'];
        $maxAmount = $validated['isUnlimited'] ? null : (float) $validated['maxAmount'];

        $commissionService->assertValidRange($this->platform->id, $minAmount, $maxAmount);

        PlatformCommissionSlab::create([
            'platform_id' => $this->platform->id,
            'min_amount' => $minAmount,
            'max_amount' => $maxAmount,
            'commission_percentage' => $validated['commissionPercentage'],
            'is_active' => $validated['isActive'],
        ]);

        flash()->success('Commission slab created successfully.');
        $this->redirectRoute('admin.platforms.commission-slabs.index', $this->platform);
    }

    public function render()
    {
        return view('livewire.admin.platform-commission-slab.platform-commission-slab-create-page');
    }
}
