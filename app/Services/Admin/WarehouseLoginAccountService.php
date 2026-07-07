<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WarehouseLoginAccountService
{
    public function findTajikistanAccount(Warehouse $warehouse): ?Admin
    {
        return Admin::query()
            ->where('role', Admin::ROLE_TAJIKISTAN_WAREHOUSE)
            ->where('warehouse_id', $warehouse->id)
            ->first();
    }

    public function findChinaAccount(): ?Admin
    {
        return Admin::query()
            ->where('role', Admin::ROLE_CHINA_WAREHOUSE)
            ->first();
    }

    public function syncTajikistanAccount(Warehouse $warehouse, array $data, bool $isCreate = false): Admin
    {
        $account = $this->findTajikistanAccount($warehouse);
        $validated = $this->validateTajikistanPayload($data, $account, $isCreate);

        $payload = [
            'name' => $warehouse->warehouse_name,
            'username' => $validated['login_username'],
            'email' => $validated['login_email'],
            'role' => Admin::ROLE_TAJIKISTAN_WAREHOUSE,
            'warehouse_id' => $warehouse->id,
            'email_verified_at' => now(),
        ];

        if (! empty($validated['login_password'])) {
            $payload['password'] = Hash::make($validated['login_password']);
        }

        if ($account) {
            $account->update($payload);

            return $account->fresh();
        }

        if (empty($validated['login_password'])) {
            throw ValidationException::withMessages([
                'login_password' => [__('admin.warehouse_login_password_required')],
            ]);
        }

        return Admin::query()->create([
            ...$payload,
            'password' => Hash::make($validated['login_password']),
        ]);
    }

    public function syncChinaAccount(array $data): Admin
    {
        $account = $this->findChinaAccount();
        $validated = $this->validateChinaPayload($data, $account);

        $payload = [
            'name' => 'China Warehouse',
            'username' => $validated['login_username'],
            'email' => $validated['login_email'],
            'role' => Admin::ROLE_CHINA_WAREHOUSE,
            'warehouse_id' => null,
            'email_verified_at' => now(),
        ];

        if (! empty($validated['login_password'])) {
            $payload['password'] = Hash::make($validated['login_password']);
        }

        if ($account) {
            $account->update($payload);

            return $account->fresh();
        }

        if (empty($validated['login_password'])) {
            throw ValidationException::withMessages([
                'login_password' => [__('admin.warehouse_login_password_required')],
            ]);
        }

        return Admin::query()->create([
            ...$payload,
            'password' => Hash::make($validated['login_password']),
        ]);
    }

    public function deleteTajikistanAccount(Warehouse $warehouse): void
    {
        $account = $this->findTajikistanAccount($warehouse);

        if ($account) {
            $account->delete();
        }
    }

    protected function validateTajikistanPayload(array $data, ?Admin $account, bool $isCreate): array
    {
        $adminId = $account?->id;

        $rules = [
            'login_username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($adminId)],
            'login_email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'login_password' => [$isCreate || ! $account ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];

        return validator($data, $rules)->validate();
    }

    protected function validateChinaPayload(array $data, ?Admin $account): array
    {
        $adminId = $account?->id;

        $rules = [
            'login_username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($adminId)],
            'login_email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'login_password' => [$account ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        return validator($data, $rules)->validate();
    }
}
