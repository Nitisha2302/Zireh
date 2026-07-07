<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'Paid',
                'code' => OrderStatus::CODE_PAID,
                'color' => 'info',
                'sort_order' => 10,
            ],
            [
                'name' => 'Received at China Warehouse',
                'code' => OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
                'color' => 'primary',
                'sort_order' => 20,
            ],
            [
                'name' => 'Preparing for Shipment',
                'code' => OrderStatus::CODE_PREPARING_FOR_SHIPMENT,
                'color' => 'warning',
                'sort_order' => 30,
            ],
            [
                'name' => 'Shipped to Tajikistan',
                'code' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
                'color' => 'primary',
                'sort_order' => 40,
            ],
            [
                'name' => 'Arrived in Tajikistan',
                'code' => OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
                'color' => 'info',
                'sort_order' => 50,
            ],
            [
                'name' => 'Sorting Center',
                'code' => OrderStatus::CODE_SORTING_CENTER,
                'color' => 'secondary',
                'sort_order' => 60,
            ],
            [
                'name' => 'Sent to Selected Warehouse',
                'code' => OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
                'color' => 'warning',
                'sort_order' => 70,
            ],
            [
                'name' => 'Ready for Pickup',
                'code' => OrderStatus::CODE_READY_FOR_PICKUP,
                'color' => 'success',
                'sort_order' => 80,
            ],
            [
                'name' => 'Delivered to Customer',
                'code' => OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
                'color' => 'success',
                'sort_order' => 90,
            ],
            [
                'name' => 'Cancelled',
                'code' => OrderStatus::CODE_CANCELLED,
                'color' => 'danger',
                'sort_order' => 100,
            ],
        ];

        foreach ($defaults as $status) {
            OrderStatus::withTrashed()->updateOrCreate(
                ['code' => $status['code']],
                [
                    'name' => $status['name'],
                    'color' => $status['color'],
                    'sort_order' => $status['sort_order'],
                    'is_system' => true,
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }

        OrderStatus::query()
            ->whereNotIn('code', OrderStatus::SYSTEM_CODES)
            ->where('is_system', true)
            ->update(['is_active' => false]);
    }
}
