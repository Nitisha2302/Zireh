<?php

use App\Models\Lesson;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTranslatableContent(array $titles, array $descriptions): array
{
    return [
        'title' => $titles,
        'description' => $descriptions,
        'image' => 'content/sample.jpg',
        'is_active' => true,
    ];
}

it('lists active lessons for guests without token', function () {
    Lesson::create(makeTranslatableContent(
        ['en' => 'Active Lesson', 'ru' => 'Активный урок', 'tg' => 'Дарси фаъол'],
        ['en' => 'Visible lesson', 'ru' => 'Видимый урок', 'tg' => 'Дарси намоён'],
    ));

    Lesson::create([
        ...makeTranslatableContent(
            ['en' => 'Hidden Lesson', 'ru' => 'Скрытый урок', 'tg' => 'Дарси пинҳон'],
            ['en' => 'Hidden lesson', 'ru' => 'Скрытый урок', 'tg' => 'Дарси пинҳон'],
        ),
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/public/lessons')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.title', 'Active Lesson')
        ->assertJsonPath('data.items.0.status', 'active');

    $this->getJson('/api/v1/lessons')
        ->assertOk()
        ->assertJsonCount(1, 'data.items');
});

it('returns lesson content in requested accept language', function () {
    Lesson::create(makeTranslatableContent(
        ['en' => 'English Lesson', 'ru' => 'Русский урок', 'tg' => 'Дарси тоҷикӣ'],
        ['en' => 'English description', 'ru' => 'Русское описание', 'tg' => 'Тавсифи тоҷикӣ'],
    ));

    $this->getJson('/api/v1/public/lessons', ['Accept-Language' => 'ru'])
        ->assertOk()
        ->assertHeader('Content-Language', 'ru')
        ->assertJsonPath('data.language', 'ru')
        ->assertJsonPath('data.items.0.title', 'Русский урок')
        ->assertJsonPath('data.items.0.description', 'Русское описание');

    $this->getJson('/api/v1/public/lessons', ['Accept-Language' => 'tg'])
        ->assertOk()
        ->assertHeader('Content-Language', 'tg')
        ->assertJsonPath('data.language', 'tg')
        ->assertJsonPath('data.items.0.title', 'Дарси тоҷикӣ')
        ->assertJsonPath('data.items.0.description', 'Тавсифи тоҷикӣ');
});

it('falls back to english for unsupported accept language', function () {
    Lesson::create(makeTranslatableContent(
        ['en' => 'English Lesson', 'ru' => 'Русский урок', 'tg' => 'Дарси тоҷикӣ'],
        ['en' => 'English description', 'ru' => 'Русское описание', 'tg' => 'Тавсифи тоҷикӣ'],
    ));

    $this->getJson('/api/v1/public/lessons', ['Accept-Language' => 'fr'])
        ->assertOk()
        ->assertHeader('Content-Language', 'en')
        ->assertJsonPath('data.language', 'en')
        ->assertJsonPath('data.items.0.title', 'English Lesson');
});

it('lists active news for guests without token', function () {
    News::create(makeTranslatableContent(
        ['en' => 'Active News', 'ru' => 'Активные новости', 'tg' => 'Хабари фаъол'],
        ['en' => 'Visible news', 'ru' => 'Видимые новости', 'tg' => 'Хабари намоён'],
    ));

    News::create([
        ...makeTranslatableContent(
            ['en' => 'Hidden News', 'ru' => 'Скрытые новости', 'tg' => 'Хабари пинҳон'],
            ['en' => 'Hidden news', 'ru' => 'Скрытые новости', 'tg' => 'Хабари пинҳон'],
        ),
        'is_active' => false,
    ]);

    $this->getJson('/api/v1/public/news', ['Accept-Language' => 'ru'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.title', 'Активные новости')
        ->assertJsonPath('data.items.0.status', 'active');
});

it('paginates lessons and news listings', function () {
    foreach (range(1, 3) as $index) {
        Lesson::create(makeTranslatableContent(
            ['en' => "Lesson {$index}", 'ru' => "Урок {$index}", 'tg' => "Дарс {$index}"],
            ['en' => "Description {$index}", 'ru' => "Описание {$index}", 'tg' => "Тавсиф {$index}"],
        ));
    }

    $this->getJson('/api/v1/public/lessons?per_page=2&page=2')
        ->assertOk()
        ->assertJsonPath('data.pagination.page', 2)
        ->assertJsonPath('data.pagination.per_page', 2)
        ->assertJsonCount(1, 'data.items');
});
