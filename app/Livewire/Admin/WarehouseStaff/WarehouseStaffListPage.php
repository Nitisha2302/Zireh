<?php

namespace App\Livewire\Admin\WarehouseStaff;

use App\Models\Admin;
use App\Services\Admin\WarehouseStaffService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin', ['title' => 'Warehouse Staff'])]
class WarehouseStaffListPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->deleteId = $id;
        sweetalert()->showDenyButton()->info(__('admin.warehouse_staff_delete_confirm'));
    }

    #[On('sweetalert:confirmed')]
    public function onConfirmed(WarehouseStaffService $service): void
    {
        if (! $this->deleteId) {
            return;
        }

        $admin = Admin::query()->findOrFail($this->deleteId);
        $service->delete($admin);
        $this->deleteId = null;
        flash()->success(__('admin.warehouse_staff_deleted'));
    }

    #[On('sweetalert:denied')]
    public function onDenied(): void
    {
        $this->deleteId = null;
        flash()->info(__('admin.warehouse_staff_delete_cancelled'));
    }

    public function render(WarehouseStaffService $service)
    {
        return view('livewire.admin.warehouse-staff.warehouse-staff-list-page', [
            'staff' => $service->paginate(
                $this->search ?: null,
                $this->roleFilter ?: null,
            ),
            'roles' => [
                Admin::ROLE_CHINA_WAREHOUSE => __('admin.role_china_warehouse'),
                Admin::ROLE_TAJIKISTAN_WAREHOUSE => __('admin.role_tajikistan_warehouse'),
            ],
        ])->title(__('admin.warehouse_staff'));
    }
}
