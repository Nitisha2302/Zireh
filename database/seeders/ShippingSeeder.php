<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $cargo = ShippingMethod::query()->updateOrCreate(
            ['code' => 'cargo'],
            [
                'name' => 'Cargo',
                'volumetric_divisor' => 6000,
                'minimum_charge' => 100,
                'is_active' => true,
            ]
        );

        $air = ShippingMethod::query()->updateOrCreate(
            ['code' => 'air'],
            [
                'name' => 'Air',
                'volumetric_divisor' => 5000,
                'minimum_charge' => 150,
                'is_active' => true,
            ]
        );

        $this->seedRates($cargo, [
            [0, 5, 25],
            [5, 10, 22],
            [10, 20, 20],
            [20, 50, 18],
            [50, 100, 16],
        ]);

        $this->seedRates($air, [
            [0, 2, 55],
            [2, 5, 50],
            [5, 10, 48],
            [10, 20, 45],
        ]);
    }

    /**
     * @param  list<array{0: float|int, 1: float|int, 2: float|int}>  $ranges
     */
    protected function seedRates(ShippingMethod $method, array $ranges): void
    {
        foreach ($ranges as [$min, $max, $rate]) {
            ShippingRate::query()->updateOrCreate(
                [
                    'shipping_method_id' => $method->id,
                    'min_weight' => $min,
                    'max_weight' => $max,
                ],
                [
                    'rate_per_kg' => $rate,
                    'is_active' => true,
                ]
            );
        }
    }
}
