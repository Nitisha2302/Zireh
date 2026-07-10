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
                'name' => [
                    'en' => 'Order Created',
                    'ru' => 'Заказ создан',
                    'tg' => 'Фармоиш эҷод шуд',
                ],
                'code' => OrderStatus::CODE_PAID,
                'color' => 'info',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Received at China Warehouse',
                    'ru' => 'Получен на складе в Китае',
                    'tg' => 'Дар анбори Чин қабул шуд',
                ],
                'code' => OrderStatus::CODE_RECEIVED_AT_CHINA_WAREHOUSE,
                'color' => 'primary',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'In Transit',
                    'ru' => 'В пути',
                    'tg' => 'Дар роҳ',
                ],
                'code' => OrderStatus::CODE_SHIPPED_TO_TAJIKISTAN,
                'color' => 'primary',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Customs Clearance',
                    'ru' => 'Таможенное оформление',
                    'tg' => 'Расмиёти гумрукӣ',
                ],
                'code' => OrderStatus::CODE_ARRIVED_IN_TAJIKISTAN,
                'color' => 'warning',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Sorting',
                    'ru' => 'Сортировка',
                    'tg' => 'Ҷудокунӣ',
                ],
                'code' => OrderStatus::CODE_SORTING_CENTER,
                'color' => 'secondary',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Ready for Pickup',
                    'ru' => 'Готов к выдаче',
                    'tg' => 'Омода ба гирифтан',
                ],
                'code' => OrderStatus::CODE_READY_FOR_PICKUP,
                'color' => 'success',
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Delivered',
                    'ru' => 'Доставлен',
                    'tg' => 'Супорида шуд',
                ],
                'code' => OrderStatus::CODE_DELIVERED_TO_CUSTOMER,
                'color' => 'success',
                'sort_order' => 70,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Cancelled',
                    'ru' => 'Отменён',
                    'tg' => 'Бекор шуд',
                ],
                'code' => OrderStatus::CODE_CANCELLED,
                'color' => 'danger',
                'sort_order' => 80,
                'is_active' => true,
            ],
            // Legacy statuses — kept for historical orders, hidden from active workflow.
            [
                'name' => [
                    'en' => 'Preparing for Shipment',
                    'ru' => 'Подготовка к отправке',
                    'tg' => 'Омодасозӣ барои фиристодан',
                ],
                'code' => OrderStatus::CODE_PREPARING_FOR_SHIPMENT,
                'color' => 'warning',
                'sort_order' => 90,
                'is_active' => false,
            ],
            [
                'name' => [
                    'en' => 'Sent to Selected Warehouse',
                    'ru' => 'Отправлен на выбранный склад',
                    'tg' => 'Ба анбори интихобшуда фиристода шуд',
                ],
                'code' => OrderStatus::CODE_SENT_TO_SELECTED_WAREHOUSE,
                'color' => 'warning',
                'sort_order' => 100,
                'is_active' => false,
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
                    'is_active' => $status['is_active'],
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
