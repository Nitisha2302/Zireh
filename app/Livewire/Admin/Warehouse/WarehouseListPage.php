<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\Warehouse;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Warehouse List'])]
class WarehouseListPage extends Component
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

    public function toggleStatus(int $id): void
    {
        $warehouse = Warehouse::findOrFail($id);

        $warehouse->update([
            'status' => $warehouse->isActive()
                ? Warehouse::STATUS_INACTIVE
                : Warehouse::STATUS_ACTIVE,
        ]);

        flash()->success(__('admin.warehouse_status_updated'));
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info(__('admin.warehouse_delete_confirm'));
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(): void
    {
        if (! $this->deleteId) {
            return;
        }

        $warehouse = Warehouse::findOrFail($this->deleteId);
        app(FileManager::class)->delete($warehouse->image);
        $warehouse->delete();
        $this->deleteId = null;
        flash()->success(__('admin.warehouse_deleted'));
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info(__('admin.warehouse_delete_cancelled'));
    }

    public function render()
    {
        $warehouses = Warehouse::query()
            ->when($this->search, function ($query): void {
                $search = $this->search;
                $query->where(function ($query) use ($search): void {
                    $query->where('warehouse_name', 'like', "%{$search}%")
                        ->orWhere('warehouse_code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.warehouse.warehouse-list-page', [
            'warehouses' => $warehouses,
            'statuses' => Warehouse::statuses(),
            'stats' => [
                'total' => Warehouse::count(),
                'active' => Warehouse::where('status', Warehouse::STATUS_ACTIVE)->count(),
                'inactive' => Warehouse::where('status', Warehouse::STATUS_INACTIVE)->count(),
            ],
        ])->title(__('admin.warehouse_list'));
    }

    protected function sortableFields(): array
    {
        return [
            'warehouse_name',
            'warehouse_code',
            'city',
            'state',
            'country',
            'status',
            'created_at',
        ];
    }
}
