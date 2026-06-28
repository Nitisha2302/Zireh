<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerManagementService
{
    public function update(User $customer, array $data): User
    {
        $attributes = [];

        foreach ([
            'name',
            'phone',
            'email',
            'status',
            'preferred_language',
            'password',
            'device_token',
            'profile_photo',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            }
        }

        $customer->update($attributes);

        return $customer->fresh();
    }

    public function toggleStatus(User $customer): User
    {
        $customer->update([
            'status' => $customer->isActive() ? User::STATUS_INACTIVE : User::STATUS_ACTIVE,
        ]);

        return $customer->fresh();
    }

    public function delete(User $customer, FileManager $fileManager): void
    {
        DB::transaction(function () use ($customer, $fileManager): void {
            $fileManager->delete($customer->profile_photo);
            $customer->tokens()->delete();
            $customer->delete();
        });
    }
}
