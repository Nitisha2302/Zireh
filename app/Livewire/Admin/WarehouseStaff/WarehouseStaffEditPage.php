<?php

namespace App\Livewire\Admin\WarehouseStaff;

use App\Models\Admin;
use App\Models\Warehouse;
use App\Services\Admin\WarehouseStaffService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'Edit Warehouse Staff'])]
class WarehouseStaffEditPage extends Component
{
    public Admin $admin;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $role = Admin::ROLE_CHINA_WAREHOUSE;

    public ?int $warehouse_id = null;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(Admin $admin): void
    {
        if (! $admin->isWarehouseStaff()) {
            abort(404);
        }

        $this->admin = $admin;
        $this->name = $admin->name;
        $this->username = $admin->username;
        $this->email = $admin->email;
        $this->role = $admin->role;
        $this->warehouse_id = $admin->warehouse_id;
    }

    public function updatedRole(): void
    {
        if ($this->role === Admin::ROLE_CHINA_WAREHOUSE) {
            $this->warehouse_id = null;
        }
    }

    public function save(WarehouseStaffService $service): void
    {
        try {
            $service->update($this->admin, [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'role' => $this->role,
                'warehouse_id' => $this->warehouse_id,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ]);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        flash()->success(__('admin.warehouse_staff_updated'));
        $this->redirectRoute('admin.warehouse-staff.index');
    }

    public function render()
    {
        return view('livewire.admin.warehouse-staff.warehouse-staff-form-page', [
            'warehouses' => Warehouse::query()->where('status', Warehouse::STATUS_ACTIVE)->orderBy('warehouse_name')->get(),
            'roles' => [
                Admin::ROLE_CHINA_WAREHOUSE => __('admin.role_china_warehouse'),
                Admin::ROLE_TAJIKISTAN_WAREHOUSE => __('admin.role_tajikistan_warehouse'),
            ],
            'isEdit' => true,
        ])->title(__('admin.edit_warehouse_staff'));
    }
}
