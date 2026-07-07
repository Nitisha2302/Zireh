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
        ->and(count($codes))->toBe(10);
});
