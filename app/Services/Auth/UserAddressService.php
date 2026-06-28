<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAddressService
{
    public function list(User $user)
    {
        return $user->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
    }

    public function create(User $user, array $data): UserAddress
    {
        return DB::transaction(function () use ($user, $data) {
            $isDefault = (bool) ($data['is_default'] ?? false);

            if ($isDefault || ! $user->addresses()->exists()) {
                $this->clearDefault($user);
                $isDefault = true;
            }

            return $user->addresses()->create([
                ...$this->addressAttributes($data),
                'is_default' => $isDefault,
            ]);
        });
    }

    public function update(User $user, UserAddress $address, array $data): UserAddress
    {
        $this->ensureOwnership($user, $address);

        return DB::transaction(function () use ($user, $address, $data) {
            $isDefault = array_key_exists('is_default', $data)
                ? (bool) $data['is_default']
                : $address->is_default;

            if ($isDefault) {
                $this->clearDefault($user, $address->id);
            }

            $address->update([
                ...$this->addressAttributes($data, $address),
                'is_default' => $isDefault,
            ]);

            return $address->fresh();
        });
    }

    public function delete(User $user, UserAddress $address): void
    {
        $this->ensureOwnership($user, $address);

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $user->addresses()->latest('id')->first();

                if ($next) {
                    $next->forceFill(['is_default' => true])->save();
                }
            }
        });
    }

    public function setDefault(User $user, UserAddress $address): UserAddress
    {
        $this->ensureOwnership($user, $address);

        return DB::transaction(function () use ($user, $address) {
            $this->clearDefault($user, $address->id);
            $address->forceFill(['is_default' => true])->save();

            return $address->fresh();
        });
    }

    protected function addressAttributes(array $data, ?UserAddress $address = null): array
    {
        $fields = [
            'full_name',
            'phone',
            'alternate_phone',
            'address_line_1',
            'address_line_2',
            'landmark',
            'city',
            'state',
            'country',
            'postal_code',
            'address_type',
            'latitude',
            'longitude',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $attributes[$field] = $data[$field];
            } elseif ($address) {
                $attributes[$field] = $address->{$field};
            }
        }

        return $attributes;
    }

    protected function clearDefault(User $user, ?int $exceptId = null): void
    {
        $query = $user->addresses()->where('is_default', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }

    protected function ensureOwnership(User $user, UserAddress $address): void
    {
        if ($address->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'address' => [__('api.address_not_found')],
            ]);
        }
    }
}
