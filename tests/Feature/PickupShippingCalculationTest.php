<?php

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\Shipping\ShippingRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies the higher of weight and volume pickup shipping costs', function () {
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    $service = app(ShippingRateService::class);

    $weightWins = $service->calculatePickupShipping($method, 5, 20, 20, 20);
    expect($weightWins['applied_method'])->toBe('weight')
        ->and($weightWins['shipping_cost'])->toBe(50.0);

    $volumeWins = $service->calculatePickupShipping($method, 1, 50, 50, 50);
    expect($volumeWins['applied_method'])->toBe('volume')
        ->and($volumeWins['shipping_cost'])->toBeGreaterThan($volumeWins['weight_cost_tjs']);
});

it('calculates pickup shipping for tiered admin rates including low volumetric weight', function () {
    $method = ShippingMethod::create([
        'name' => 'Ground cargo',
        'code' => 'ground_cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 0.10,
        'max_weight' => 100,
        'rate_per_kg' => 12,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'min_weight' => 101,
        'max_weight' => 200,
        'rate_per_kg' => 150,
        'is_active' => true,
    ]);

    $service = app(ShippingRateService::class);

    $result = $service->calculatePickupShipping($method, 25, 10, 10, 10);

    expect($result['applied_method'])->toBe('weight')
        ->and($result['shipping_cost'])->toBe(300.0)
        ->and($result['weight_cost_tjs'])->toBe(300.0);
});
