<?php

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Warehouse;
use App\Support\Geo\DistanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makeWarehouseUser(): User
{
    return User::factory()->create();
}

function makeUserAddress(User $user, array $overrides = []): UserAddress
{
    return $user->addresses()->create(array_merge([
        'full_name' => 'Test User',
        'phone' => '+992901111111',
        'address_line_1' => 'Rudaki Avenue',
        'city' => 'Dushanbe',
        'state' => 'DRS',
        'country' => 'Tajikistan',
        'postal_code' => '734000',
        'address_type' => UserAddress::TYPE_HOME,
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'is_default' => true,
    ], $overrides));
}

function makeActiveWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-01',
        'contact_person' => 'Rustam',
        'contact_number' => '+992901234567',
        'country' => 'Tajikistan',
        'state' => 'DRS',
        'city' => 'Dushanbe',
        'address' => 'Central Street',
        'latitude' => 38.5600,
        'longitude' => 68.7870,
        'status' => Warehouse::STATUS_ACTIVE,
    ], $overrides));
}

it('returns warehouses ordered by nearest distance to address', function () {
    $user = makeWarehouseUser();
    $address = makeUserAddress($user);

    $nearest = makeActiveWarehouse([
        'warehouse_name' => 'Nearest Warehouse',
        'warehouse_code' => 'NEAR-01',
        'latitude' => 38.5601,
        'longitude' => 68.7871,
    ]);

    $farther = makeActiveWarehouse([
        'warehouse_name' => 'Far Warehouse',
        'warehouse_code' => 'FAR-01',
        'latitude' => 40.2833,
        'longitude' => 69.6333,
        'city' => 'Khujand',
    ]);

    makeActiveWarehouse([
        'warehouse_name' => 'Inactive Warehouse',
        'warehouse_code' => 'OFF-01',
        'status' => Warehouse::STATUS_INACTIVE,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/warehouses?address_id='.$address->id)
        ->assertOk()
        ->assertJsonPath('data.origin.address_id', $address->id)
        ->assertJsonPath('data.origin.latitude', 38.5598)
        ->assertJsonPath('data.origin.longitude', 68.787)
        ->assertJsonPath('data.warehouses.0.id', $nearest->id)
        ->assertJsonPath('data.warehouses.1.id', $farther->id)
        ->assertJsonCount(2, 'data.warehouses');

    $firstDistance = $response->json('data.warehouses.0.distance_km');
    $secondDistance = $response->json('data.warehouses.1.distance_km');

    expect($firstDistance)->toBeLessThan($secondDistance)
        ->and($firstDistance)->toBe(round(DistanceCalculator::haversineKm(38.5598, 68.7870, 38.5601, 68.7871), 2));
});

it('rejects warehouse listing when address has no coordinates', function () {
    $user = makeWarehouseUser();
    $address = makeUserAddress($user, [
        'latitude' => null,
        'longitude' => null,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/warehouses?address_id='.$address->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['address_id']);
});

it('rejects warehouse listing for another users address', function () {
    $user = makeWarehouseUser();
    $otherUser = makeWarehouseUser();
    $address = makeUserAddress($otherUser);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/warehouses?address_id='.$address->id)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['address_id']);
});

it('requires authentication for warehouse listing', function () {
    $this->getJson('/api/v1/auth/warehouses?address_id=1')
        ->assertUnauthorized();
});
