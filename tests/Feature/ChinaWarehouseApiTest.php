<?php

use App\Models\Setting;
use App\Support\Elim\ElimWarehouseAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedChinaWarehouseAddress(array $overrides = []): void
{
    Setting::create([
        'key' => ElimWarehouseAddress::SETTING_KEY,
        'value' => json_encode(array_merge([
            'name' => 'China WH',
            'phone' => '02812345678',
            'mobile' => '13800138000',
            'address' => '广州市天河区体育西路123号',
            'province' => '广东省',
            'city' => '广州市',
            'area' => '天河区',
        ], $overrides), JSON_UNESCAPED_UNICODE),
    ]);
}

it('returns china warehouse details from public api without authentication', function () {
    seedChinaWarehouseAddress();

    $this->getJson('/api/v1/china-warehouse')
        ->assertOk()
        ->assertJsonPath('message', __('api.china_warehouse_fetched'))
        ->assertJsonPath('data.name', 'China WH')
        ->assertJsonPath('data.phone', '02812345678')
        ->assertJsonPath('data.mobile', '13800138000')
        ->assertJsonPath('data.address', '广州市天河区体育西路123号')
        ->assertJsonPath('data.province', '广东省')
        ->assertJsonPath('data.city', '广州市')
        ->assertJsonPath('data.area', '天河区');

    $this->getJson('/api/v1/public/china-warehouse')
        ->assertOk()
        ->assertJsonPath('data.name', 'China WH');
});

it('returns not found when china warehouse address is not configured', function () {
    $this->getJson('/api/v1/china-warehouse')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('api.elim_warehouse_address_missing'));
});

it('returns not found when china warehouse address is incomplete', function () {
    seedChinaWarehouseAddress(['city' => '']);

    $this->getJson('/api/v1/china-warehouse')
        ->assertNotFound()
        ->assertJsonPath('message', __('api.elim_warehouse_address_missing'));
});
