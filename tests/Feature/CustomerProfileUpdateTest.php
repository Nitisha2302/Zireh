<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('updates profile with full_name and email via patch json', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/profile', [
        'full_name' => 'New Name',
        'email' => 'new@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'New Name')
        ->assertJsonPath('data.email', 'new@example.com');

    $user->refresh();

    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

it('updates profile when client sends name instead of full_name', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/profile', [
        'name' => 'Patched Name',
        'email' => 'patched@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Patched Name')
        ->assertJsonPath('data.email', 'patched@example.com');

    $user->refresh();

    expect($user->name)->toBe('Patched Name')
        ->and($user->email)->toBe('patched@example.com');
});

it('does not clear email when only name is updated', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'keep@example.com',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/profile', [
        'full_name' => 'Only Name Changed',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Only Name Changed')
        ->assertJsonPath('data.email', 'keep@example.com');

    $user->refresh();

    expect($user->email)->toBe('keep@example.com');
});

it('updates profile warehouse_id', function () {
    $warehouse = Warehouse::create([
        'warehouse_name' => 'Dushanbe Hub',
        'warehouse_code' => 'DUS-01',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000000',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Main Street 1',
        'latitude' => 38.5598,
        'longitude' => 68.7870,
        'status' => Warehouse::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/auth/profile', [
        'warehouse_id' => $warehouse->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.warehouse_id', $warehouse->id)
        ->assertJsonPath('data.warehouse.id', $warehouse->id)
        ->assertJsonPath('data.warehouse.warehouse_name', 'Dushanbe Hub');

    $user->refresh();

    expect($user->warehouse_id)->toBe($warehouse->id);
});

it('updates profile via multipart patch', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Sanctum::actingAs($user);

    $this->patch('/api/v1/auth/profile', [
        'name' => 'Multipart Name',
        'email' => 'multipart@example.com',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Multipart Name')
        ->assertJsonPath('data.email', 'multipart@example.com');
});
