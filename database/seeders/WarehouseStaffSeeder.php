<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WarehouseStaffSeeder extends Seeder
{
    public function run(): void
    {
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

        Admin::updateOrCreate(
            ['username' => 'china_warehouse'],
            [
                'name' => 'China Warehouse',
                'email' => 'china.warehouse@example.com',
                'role' => Admin::ROLE_CHINA_WAREHOUSE,
                'warehouse_id' => null,
                'password' => Hash::make('warehouse123'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        Admin::updateOrCreate(
            ['username' => 'tj_warehouse'],
            [
                'name' => 'Tajikistan Warehouse',
                'email' => 'tj.warehouse@example.com',
                'role' => Admin::ROLE_TAJIKISTAN_WAREHOUSE,
                'warehouse_id' => $warehouse->id,
                'password' => Hash::make('warehouse123'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
    }
}
