<?php

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseWorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makeHoursWarehouse(array $overrides = []): Warehouse
{
    return Warehouse::create(array_merge([
        'warehouse_name' => 'Hours Warehouse',
        'warehouse_code' => 'HRS-01',
        'contact_person' => 'Rustam',
        'contact_number' => '555332211',
        'country' => 'Tajikistan',
        'state' => 'DRS',
        'city' => 'Dushanbe',
        'address' => '32 Microdistrict',
        'latitude' => 38.5600,
        'longitude' => 68.7870,
        'status' => Warehouse::STATUS_ACTIVE,
    ], $overrides));
}

function seedDefaultWorkingHours(Warehouse $warehouse): void
{
    foreach (WarehouseWorkingHour::defaultWeek() as $day => $row) {
        $warehouse->workingHours()->create([
            'day_of_week' => $day,
            'is_closed' => $row['is_closed'],
            'opens_at' => $row['is_closed'] ? null : $row['opens_at'],
            'closes_at' => $row['is_closed'] ? null : $row['closes_at'],
            'break_starts_at' => $row['is_closed'] ? null : $row['break_starts_at'],
            'break_ends_at' => $row['is_closed'] ? null : $row['break_ends_at'],
        ]);
    }
}

it('is open during working hours in Dushanbe time', function () {
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-18 05:00:00', 'UTC'));

    expect($warehouse->fresh()->isOpenNow())->toBeTrue();
});

it('is closed during the break window', function () {
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-18 08:00:00', 'UTC'));

    expect($warehouse->fresh()->isOpenNow())->toBeFalse();
});

it('is closed on a closed weekday', function () {
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-20 05:00:00', 'UTC'));

    expect($warehouse->fresh()->isOpenNow())->toBeFalse();
});

it('is closed when the warehouse is inactive', function () {
    $warehouse = makeHoursWarehouse(['status' => Warehouse::STATUS_INACTIVE]);
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-18 05:00:00', 'UTC'));

    expect($warehouse->fresh()->isOpenNow())->toBeFalse();
});

it('includes working hours and open status in the warehouse api', function () {
    $user = User::factory()->create();
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-18 05:00:00', 'UTC'));

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/warehouses')
        ->assertOk()
        ->assertJsonPath('data.warehouses.0.id', $warehouse->id)
        ->assertJsonPath('data.warehouses.0.is_open', true)
        ->assertJsonPath('data.warehouses.0.working_hours.0.day_name', 'Mon')
        ->assertJsonPath('data.warehouses.0.working_hours.0.is_closed', false)
        ->assertJsonPath('data.warehouses.0.working_hours.0.opens_at', '09:00')
        ->assertJsonPath('data.warehouses.0.working_hours.0.closes_at', '19:00')
        ->assertJsonPath('data.warehouses.0.working_hours.0.break_starts_at', '12:40')
        ->assertJsonPath('data.warehouses.0.working_hours.0.break_ends_at', '14:00')
        ->assertJsonPath('data.warehouses.0.working_hours.6.day_name', 'Sun')
        ->assertJsonPath('data.warehouses.0.working_hours.6.is_closed', true)
        ->assertJsonCount(7, 'data.warehouses.0.working_hours');
});

it('includes working hours on public warehouse listing and view', function () {
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Carbon::setTestNow(Carbon::parse('2026-09-18 05:00:00', 'UTC'));

    $this->getJson('/api/v1/public/warehouses')
        ->assertOk()
        ->assertJsonPath('data.warehouses.0.id', $warehouse->id)
        ->assertJsonPath('data.warehouses.0.is_open', true)
        ->assertJsonPath('data.warehouses.0.working_hours.0.opens_at', '09:00')
        ->assertJsonPath('data.warehouses.0.working_hours.6.is_closed', true);

    $this->getJson('/api/v1/public/warehouses/'.$warehouse->id)
        ->assertOk()
        ->assertJsonPath('data.id', $warehouse->id)
        ->assertJsonPath('data.contact_number', '555332211')
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.working_hours.0.day_name', 'Mon')
        ->assertJsonPath('data.working_hours.0.opens_at', '09:00')
        ->assertJsonPath('data.working_hours.0.break_starts_at', '12:40')
        ->assertJsonPath('data.working_hours.6.is_closed', true)
        ->assertJsonCount(7, 'data.working_hours');

    $this->getJson('/api/v1/warehouses/'.$warehouse->id)
        ->assertOk()
        ->assertJsonPath('data.id', $warehouse->id)
        ->assertJsonPath('data.working_hours.0.closes_at', '19:00');
});

it('returns warehouse details with working hours on the authenticated view', function () {
    $user = User::factory()->create();
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/warehouses/'.$warehouse->id)
        ->assertOk()
        ->assertJsonPath('data.id', $warehouse->id)
        ->assertJsonPath('data.working_hours.5.day_name', 'Sat')
        ->assertJsonPath('data.working_hours.6.day_name', 'Sun')
        ->assertJsonPath('data.working_hours.6.is_closed', true);
});

it('hides inactive warehouses from the warehouse view api', function () {
    $warehouse = makeHoursWarehouse(['status' => Warehouse::STATUS_INACTIVE]);

    $this->getJson('/api/v1/public/warehouses/'.$warehouse->id)
        ->assertNotFound();
});

it('includes working hours on the selected profile warehouse', function () {
    $warehouse = makeHoursWarehouse();
    seedDefaultWorkingHours($warehouse);

    $user = User::factory()->create(['warehouse_id' => $warehouse->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.warehouse.id', $warehouse->id)
        ->assertJsonPath('data.warehouse.working_hours.0.opens_at', '09:00')
        ->assertJsonPath('data.warehouse.working_hours.6.is_closed', true);
});
