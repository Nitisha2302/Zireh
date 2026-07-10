<?php

use App\Models\OrderStatus;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(OrderStatusSeeder::class);
});

it('lists active order statuses sorted by sort_order via api', function () {
    $response = $this->getJson('/api/v1/order-statuses');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'statuses' => [
                    '*' => ['code', 'name', 'color', 'is_system', 'sort_order'],
                ],
            ],
        ]);

    $codes = collect($response->json('data.statuses'))->pluck('code')->all();
    $sortOrders = collect($response->json('data.statuses'))->pluck('sort_order')->all();

    expect($codes[0])->toBe(OrderStatus::CODE_PAID)
        ->and($codes)->toContain(OrderStatus::CODE_DELIVERED_TO_CUSTOMER)
        ->and($sortOrders)->toBe(collect($sortOrders)->sort()->values()->all())
        ->and(count($codes))->toBe(8)
        ->and($response->json('data.statuses.0.name'))->toBe('Order Created');
});

it('returns localized order status names from api', function () {
    $response = $this->withHeader('Accept-Language', 'ru')
        ->getJson('/api/v1/order-statuses');

    $response->assertOk();

    $paid = collect($response->json('data.statuses'))
        ->firstWhere('code', OrderStatus::CODE_PAID);

    expect($paid['name'])->toBe('Заказ создан');
});
