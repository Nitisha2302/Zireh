<?php

namespace App\Services\Warehouse;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Warehouse;
use App\Support\Geo\DistanceCalculator;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function listNearestToAddress(User $user, int $addressId): array
    {
        $address = $user->addresses()->find($addressId);

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => [__('api.address_not_found')],
            ]);
        }

        $this->ensureAddressHasCoordinates($address);

        $latitude = (float) $address->latitude;
        $longitude = (float) $address->longitude;

        $warehouses = Warehouse::query()
            ->where('status', Warehouse::STATUS_ACTIVE)
            ->get()
            ->map(function (Warehouse $warehouse) use ($latitude, $longitude): Warehouse {
                $warehouse->setAttribute(
                    'distance_km',
                    DistanceCalculator::haversineKm(
                        $latitude,
                        $longitude,
                        (float) $warehouse->latitude,
                        (float) $warehouse->longitude
                    )
                );

                return $warehouse;
            })
            ->sortBy('distance_km')
            ->values();

        return [
            'origin' => [
                'address_id' => $address->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
            ],
            'warehouses' => $warehouses,
        ];
    }

    public function listActive(): array
    {
        return [
            'warehouses' => Warehouse::query()
                ->where('status', Warehouse::STATUS_ACTIVE)
                ->orderBy('warehouse_name')
                ->get(),
        ];
    }

    protected function ensureAddressHasCoordinates(UserAddress $address): void
    {
        if ($address->latitude === null || $address->longitude === null) {
            throw ValidationException::withMessages([
                'address_id' => [__('api.address_coordinates_missing')],
            ]);
        }
    }
}
