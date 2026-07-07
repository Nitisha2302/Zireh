<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WarehouseStaffService
{
    public function paginate(?string $search = null, ?string $roleFilter = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->staffQuery($search, $roleFilter)->paginate($perPage);
    }

    public function create(array $data): Admin
    {
        $validated = $this->validatePayload($data);

        return Admin::query()->create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);
    }

    public function update(Admin $admin, array $data): Admin
    {
        if ($admin->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'admin' => [__('admin.warehouse_staff_super_admin_protected')],
            ]);
        }

        $validated = $this->validatePayload($data, $admin);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $admin->update($validated);

        return $admin->fresh(['warehouse']);
    }

    public function delete(Admin $admin): void
    {
        if ($admin->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'admin' => [__('admin.warehouse_staff_super_admin_protected')],
            ]);
        }

        $admin->delete();
    }

    protected function staffQuery(?string $search, ?string $roleFilter): Builder
    {
        return Admin::query()
            ->with('warehouse')
            ->whereIn('role', [Admin::ROLE_CHINA_WAREHOUSE, Admin::ROLE_TAJIKISTAN_WAREHOUSE])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($roleFilter, fn (Builder $query) => $query->where('role', $roleFilter))
            ->latest();
    }

    protected function validatePayload(array $data, ?Admin $admin = null): array
    {
        $adminId = $admin?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($adminId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'role' => ['required', Rule::in([Admin::ROLE_CHINA_WAREHOUSE, Admin::ROLE_TAJIKISTAN_WAREHOUSE])],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'password' => [$admin ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        $validated = validator($data, $rules)->validate();

        if ($validated['role'] === Admin::ROLE_TAJIKISTAN_WAREHOUSE) {
            if (empty($validated['warehouse_id'])) {
                throw ValidationException::withMessages([
                    'warehouse_id' => [__('admin.warehouse_staff_warehouse_required')],
                ]);
            }

            $warehouse = Warehouse::query()->find($validated['warehouse_id']);

            if (! $warehouse || ! $warehouse->isActive()) {
                throw ValidationException::withMessages([
                    'warehouse_id' => [__('admin.warehouse_not_available')],
                ]);
            }
        } else {
            $validated['warehouse_id'] = null;
        }

        return $validated;
    }
}
