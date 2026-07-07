<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Services\Admin\WarehouseLoginAccountService;
use Illuminate\Database\Seeder;

class WarehouseStaffSeeder extends Seeder
{
    public function run(): void
    {
        $loginAccounts = app(WarehouseLoginAccountService::class);

        $warehouse = Warehouse::query()->firstOrCreate(
            ['warehouse_code' => 'DUS-TJ-01'],
            [
                'warehouse_name' => 'Dushanbe Hub',
                'contact_person' => 'Warehouse Manager',
                'contact_number' => '+992900000000',
                'country' => Warehouse::DEFAULT_COUNTRY,
                'state' => 'Dushanbe',
                'city' => 'Dushanbe',
                'address' => 'Main Street 1',
                'latitude' => 38.5598,
                'longitude' => 68.7870,
                'status' => Warehouse::STATUS_ACTIVE,
            ]
        );

        $loginAccounts->syncTajikistanAccount($warehouse, [
            'login_username' => 'tj_warehouse',
            'login_email' => 'tj.warehouse@example.com',
            'login_password' => 'warehouse123',
            'login_password_confirmation' => 'warehouse123',
        ], isCreate: ! $loginAccounts->findTajikistanAccount($warehouse));

        $loginAccounts->syncChinaAccount([
            'login_username' => 'china_warehouse',
            'login_email' => 'china.warehouse@example.com',
            'login_password' => 'warehouse123',
            'login_password_confirmation' => 'warehouse123',
        ]);
    }
}
