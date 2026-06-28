<?php

namespace Database\Seeders;

use App\Models\Platform;
use App\Models\PlatformCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'keyword' => 'electronics',
                'name' => [
                    'en' => 'Electronics',
                    'ru' => 'Электроника',
                    'tg' => 'Электроника',
                ],
            ],
            [
                'keyword' => 'fashion',
                'name' => [
                    'en' => 'Fashion',
                    'ru' => 'Мода',
                    'tg' => 'Мӯд',
                ],
            ],
            [
                'keyword' => 'home-garden',
                'name' => [
                    'en' => 'Home & Garden',
                    'ru' => 'Дом и Сад',
                    'tg' => 'Хона ва Боғ',
                ],
            ],
            [
                'keyword' => 'beauty',
                'name' => [
                    'en' => 'Beauty',
                    'ru' => 'Красота',
                    'tg' => 'Зебоӣ',
                ],
            ],
            [
                'keyword' => 'sports',
                'name' => [
                    'en' => 'Sports',
                    'ru' => 'Спорт',
                    'tg' => 'Варзиш',
                ],
            ],
            [
                'keyword' => 'toys',
                'name' => [
                    'en' => 'Toys',
                    'ru' => 'Игрушки',
                    'tg' => 'Игрышкӯлӯр',
                ],
            ]
        ];

        $platforms = Platform::all();

        foreach ($platforms as $platform) {
            foreach ($categories as $category) {
                PlatformCategory::updateOrCreate(
                    [
                        'platform_id' => $platform->id,
                        'keyword' => $category['keyword'],
                    ],
                    [
                        'name' => $category['name'],
                        'is_active' => true,
                        'is_default' => true
                    ]
                );
            }
        }
    }
}
