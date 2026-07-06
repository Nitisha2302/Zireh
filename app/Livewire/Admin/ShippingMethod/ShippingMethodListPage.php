<?php

namespace App\Livewire\Admin\ShippingMethod;

use App\Models\ShippingMethod;
use App\Repositories\Shipping\ShippingMethodRepository;
use App\Services\Shipping\ShippingMethodService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Shipping Methods'])]
class ShippingMethodListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->statusFilter !== '';
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

    public function toggleStatus(int $id, ShippingMethodService $service): void
    {
        $service->toggleStatus(ShippingMethod::findOrFail($id));
        flash()->success(__('admin.shipping_method_status_updated'));
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info(__('admin.shipping_method_delete_confirm'));
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(ShippingMethodService $service): void
    {
        if (! $this->deleteId) {
            return;
        }

        $service->delete(ShippingMethod::findOrFail($this->deleteId));
        $this->deleteId = null;
        flash()->success(__('admin.shipping_method_deleted'));
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info(__('admin.shipping_method_delete_cancelled'));
    }

    public function render(ShippingMethodRepository $repository)
    {
        return view('livewire.admin.shipping-method.shipping-method-list-page', [
            'methods' => $repository->paginate(
                $this->search ?: null,
                $this->statusFilter ?: null,
                $this->sortField,
                $this->sortDirection
            ),
            'stats' => $repository->countStats(),
        ])->title(__('admin.shipping_methods'));
    }

    protected function sortableFields(): array
    {
        return ['name', 'code', 'minimum_charge', 'is_active', 'created_at', 'updated_at'];
    }
}
