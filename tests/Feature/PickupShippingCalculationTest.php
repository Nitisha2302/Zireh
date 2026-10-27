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

it('bills pickup shipping from weight only when mode is weight', function () {
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

    $result = app(ShippingRateService::class)->calculatePickupShipping(
        $method,
        1.5,
        50,
        50,
        50,
        null,
        'weight',
    );

    expect($result['applied_method'])->toBe('weight')
        ->and($result['shipping_cost'])->toBe(15.0)
        ->and($result['volume_cost_tjs'])->toBe(0.0)
        ->and($result['package']['length_cm'])->toBeNull()
        ->and($result['package']['weight_kg'])->toBe(1.5);
});

it('bills pickup shipping from volume only when mode is cm', function () {
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

    $result = app(ShippingRateService::class)->calculatePickupShipping(
        $method,
        20,
        50,
        40,
        30,
        null,
        'cm',
    );

    expect($result['applied_method'])->toBe('volume')
        ->and($result['weight_cost_tjs'])->toBe(0.0)
        ->and($result['package']['weight_kg'])->toBeNull()
        ->and($result['package']['volumetric_weight_kg'])->toBe(12.0)
        ->and($result['shipping_cost'])->toBe(120.0);
});

it('bills pickup from the matching weight or cm ladder', function () {
    $method = ShippingMethod::create([
        'name' => 'Cargo',
        'code' => 'cargo',
        'volumetric_divisor' => 5000,
        'minimum_charge' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_WEIGHT,
        'min_weight' => 0,
        'max_weight' => 10,
        'rate_per_kg' => 10,
        'is_active' => true,
    ]);

    ShippingRate::create([
        'shipping_method_id' => $method->id,
        'mode' => ShippingRate::MODE_CM,
        'min_weight' => 0,
        'max_weight' => 100,
        'rate_per_kg' => 20,
        'is_active' => true,
    ]);

    $service = app(ShippingRateService::class);

    $weight = $service->calculatePickupShipping($method, 1.5, 50, 50, 50, null, 'weight');
    expect($weight['shipping_cost'])->toBe(15.0)
        ->and($weight['weight_breakdown']['rate']['mode'])->toBe('weight');

    $volume = $service->calculatePickupShipping($method, 20, 50, 40, 30, null, 'cm');
    expect($volume['shipping_cost'])->toBe(240.0)
        ->and($volume['volume_breakdown']['rate']['mode'])->toBe('cm');

    $both = $service->calculatePickupShipping($method, 1.5, 50, 40, 30, null, 'both');
    expect($both['applied_method'])->toBe('both')
        ->and($both['weight_cost_tjs'])->toBe(15.0)
        ->and($both['volume_cost_tjs'])->toBe(240.0)
        ->and($both['shipping_cost'])->toBe(255.0)
        ->and($both['package']['weight_kg'])->toBe(1.5)
        ->and($both['package']['volumetric_weight_kg'])->toBe(12.0);
});
