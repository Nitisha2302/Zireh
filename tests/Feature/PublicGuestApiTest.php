<?php

use App\Models\Platform;
use App\Models\PlatformSlider;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);

    config(['services.elim.base_url' => 'https://openapi.elim.asia']);
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);
});

it('allows guest access to platform catalog routes without token', function () {
    $platform = Platform::create([
        'code' => 'taobao',
        'name' => ['en' => 'Taobao'],
        'logo' => ['en' => 'platforms/taobao.png'],
        'is_available' => true,
    ]);

    $slider = PlatformSlider::create([
        'heading' => 'Summer Sale',
        'link' => 'https://example.com',
        'image' => 'sliders/summer.png',
    ]);
    $slider->platforms()->attach($platform->id);

    $this->getJson('/api/v1/platforms')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.platforms.0.code', 'taobao');

    $this->getJson('/api/v1/public/platforms')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.platforms.0.code', 'taobao');

    $this->getJson('/api/v1/platform-sliders')
        ->assertOk()
        ->assertJsonPath('data.sliders.0.heading', 'Summer Sale');

    $this->getJson('/api/v1/public/platform-sliders')
        ->assertOk()
        ->assertJsonPath('data.sliders.0.heading', 'Summer Sale');
});

it('allows guest access to product listing and detail without token', function () {
    Http::fake([
        'https://openapi.elim.asia/v1/products/search' => Http::response([
            'items' => [
                ['id' => '1001', 'title' => 'Guest Product', 'price' => 10],
            ],
            'paginate' => ['total' => 1, 'current' => 1, 'size' => 20],
        ], 200),
        'https://openapi.elim.asia/v1/products/find' => Http::response([
            'id' => '1001',
            'title' => 'Guest Product',
            'price' => 10,
        ], 200),
    ]);

    $this->getJson('/api/v1/taobao/products?lang=en')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson('/api/v1/public/taobao/products?lang=en')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson('/api/v1/public/taobao/products/1001?lang=en')
        ->assertOk()
        ->assertJsonPath('data.id', '1001');
});

it('allows guest access to active warehouses without token', function () {
    $active = \App\Models\Warehouse::create([
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
        'status' => \App\Models\Warehouse::STATUS_ACTIVE,
    ]);

    \App\Models\Warehouse::create([
        'warehouse_name' => 'Closed Hub',
        'warehouse_code' => 'CLS-01',
        'contact_person' => 'Manager',
        'contact_number' => '+992900000001',
        'country' => 'Tajikistan',
        'state' => 'Dushanbe',
        'city' => 'Dushanbe',
        'address' => 'Side Street 2',
        'latitude' => 38.5600,
        'longitude' => 68.7880,
        'status' => \App\Models\Warehouse::STATUS_INACTIVE,
    ]);

    $this->getJson('/api/v1/warehouses')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.warehouses')
        ->assertJsonPath('data.warehouses.0.id', $active->id)
        ->assertJsonPath('data.warehouses.0.warehouse_code', 'DUS-01')
        ->assertJsonStructure([
            'data' => [
                'warehouses' => [
                    ['id', 'is_open', 'working_hours'],
                ],
            ],
        ]);

    $this->getJson('/api/v1/public/warehouses')
        ->assertOk()
        ->assertJsonCount(1, 'data.warehouses')
        ->assertJsonPath('data.warehouses.0.id', $active->id);
});

it('requires token for authenticated customer routes', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->getJson('/api/v1/auth/taobao/cart')->assertUnauthorized();
});
