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
                'name' => 'Creating',
                'code' => OrderStatus::CODE_CREATING,
                'color' => 'secondary',
                'sort_order' => 10,
            ],
            [
                'name' => 'Pending Payment',
                'code' => OrderStatus::CODE_PENDING_PAYMENT,
                'color' => 'warning',
                'sort_order' => 20,
            ],
            [
                'name' => 'Paid',
                'code' => OrderStatus::CODE_PAID,
                'color' => 'info',
                'sort_order' => 30,
            ],
            [
                'name' => 'Shipped',
                'code' => OrderStatus::CODE_SHIPPED,
                'color' => 'primary',
                'sort_order' => 40,
            ],
            [
                'name' => 'Completed',
                'code' => OrderStatus::CODE_COMPLETED,
                'color' => 'success',
                'sort_order' => 50,
            ],
            [
                'name' => 'Cancelled',
                'code' => OrderStatus::CODE_CANCELLED,
                'color' => 'danger',
                'sort_order' => 60,
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
    }
}
