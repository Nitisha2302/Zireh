<?php

namespace App\Livewire\Admin\ShippingRate;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Repositories\Shipping\ShippingRateRepository;
use App\Services\Shipping\ShippingRateService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Shipping Rates'])]
class ShippingRateListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $methodFilter = '';

    public string $statusFilter = '';

    public string $weightFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingWeightFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->methodFilter = '';
        $this->statusFilter = '';
        $this->weightFilter = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->methodFilter !== ''
            || $this->statusFilter !== ''
            || $this->weightFilter !== '';
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->sortableFields(), true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleStatus(int $id, ShippingRateService $service): void
    {
        $service->toggleStatus(ShippingRate::findOrFail($id));
        flash()->success(__('admin.shipping_rate_status_updated'));
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info(__('admin.shipping_rate_delete_confirm'));
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(ShippingRateService $service): void
    {
        if (! $this->deleteId) {
            return;
        }

        $service->delete(ShippingRate::findOrFail($this->deleteId));
        $this->deleteId = null;
        flash()->success(__('admin.shipping_rate_deleted'));
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info(__('admin.shipping_rate_delete_cancelled'));
    }

    public function render(ShippingRateRepository $repository)
    {
        $weight = $this->weightFilter !== '' && is_numeric($this->weightFilter)
            ? (float) $this->weightFilter
            : null;

        return view('livewire.admin.shipping-rate.shipping-rate-list-page', [
            'rates' => $repository->paginate(
                $this->search ?: null,
                $this->methodFilter !== '' ? (int) $this->methodFilter : null,
                $this->statusFilter ?: null,
                $weight,
                $this->sortField,
                $this->sortDirection
            ),
            'methods' => ShippingMethod::query()->orderBy('name')->get(),
            'stats' => $repository->countStats(),
        ])->title(__('admin.shipping_rates'));
    }

    protected function sortableFields(): array
    {
        return ['min_weight', 'max_weight', 'rate_per_kg', 'is_active', 'created_at', 'updated_at'];
    }
}
