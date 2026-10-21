<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.elim.base_url' => 'https://openapi.elim.asia',
        'services.elim.default_lang' => 'en',
    ]);
    Cache::flush();
    Cache::put('elim:auth:access_token', 'test-elim-token', 3600);
});

it('uploads an image to elim v2 upload-image and returns imageId and img_id', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/upload-image' => Http::response([
            'imageId' => 'image-123',
        ], 200),
    ]);

    $file = UploadedFile::fake()->image('shoe.jpg', 200, 200);

    $this->postJson('/api/v1/public/taobao/upload-image', [
        'file' => $file,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.imageId', 'image-123')
        ->assertJsonPath('data.img_id', 'image-123');

    Http::assertSent(fn (HttpClientRequest $request): bool => $request->url() === 'https://openapi.elim.asia/v2/products/upload-image');
});

it('searches taobao products by image url using elim v2 search-by-image', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/search-by-image' => Http::response([
            'paginate' => [
                'total' => 1,
                'current' => 1,
                'size' => 20,
            ],
            'items' => [
                [
                    'id' => '734467086498',
                    'title' => '纯棉T恤',
                    'title_en' => 'Cotton T-shirt',
                    'img_url' => 'https://img.alicdn.com/bao/uploaded/i3/694223667/O1CN01zSjKgp1cxXRQZsDLA_!!694223667.jpg',
                    'price' => 50,
                    'promotion_price' => 45,
                ],
            ],
        ], 200),
    ]);

    $imgUrl = 'https://img.alicdn.com/bao/uploaded/i3/694223667/O1CN01zSjKgp1cxXRQZsDLA_!!694223667.jpg';

    $this->postJson('/api/v1/public/taobao/search-by-image', [
        'imgUrl' => $imgUrl,
        'lang' => 'en',
        'page' => 1,
        'size' => 20,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.platform', 'taobao')
        ->assertJsonPath('data.items.0.id', '734467086498')
        ->assertJsonPath('data.items.0.title', 'Cotton T-shirt')
        ->assertJsonPath('data.items.0.original_title', '纯棉T恤');

    Http::assertSent(function (HttpClientRequest $request) use ($imgUrl): bool {
        return $request->url() === 'https://openapi.elim.asia/v2/products/search-by-image'
            && $request['platform'] === 'taobao'
            && $request['imgUrl'] === $imgUrl
            && $request['lang'] === 'en'
            && $request['page'] === 1
            && $request['size'] === 20;
    });
});

it('searches 1688 products by uploaded imgId using elim v2 search-by-image', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/search-by-image' => Http::response([
            'paginate' => [
                'total' => 1,
                'current' => 1,
                'size' => 20,
            ],
            'items' => [
                [
                    'id' => '99887766',
                    'title' => '批发背包',
                    'title_en' => 'Wholesale Backpack',
                    'img_url' => 'https://cbu01.alicdn.com/img/ibank/sample.jpg',
                    'price' => 30,
                ],
            ],
        ], 200),
    ]);

    $this->postJson('/api/v1/public/1688/search-by-image', [
        'imgId' => 'image-123',
        'lang' => 'ru',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.platform', '1688')
        ->assertJsonPath('data.items.0.id', '99887766')
        ->assertJsonPath('data.items.0.title', 'Wholesale Backpack');

    Http::assertSent(function (HttpClientRequest $request): bool {
        return $request->url() === 'https://openapi.elim.asia/v2/products/search-by-image'
            && $request['platform'] === 'alibaba'
            && $request['imgId'] === 'image-123'
            && $request['lang'] === 'ru';
    });
});

it('uploads and searches in one call when a file is posted to search-by-image', function () {
    Http::fake([
        'https://openapi.elim.asia/v2/products/upload-image' => Http::response([
            'imageId' => 'uploaded-img-999',
        ], 200),
        'https://openapi.elim.asia/v2/products/search-by-image' => Http::response([
            'paginate' => [
                'total' => 1,
                'current' => 1,
                'size' => 20,
            ],
            'items' => [
                [
                    'id' => '11223344',
                    'title' => '运动鞋',
                    'title_en' => 'Running Shoes',
                    'img_url' => 'https://img.alicdn.com/bao/uploaded/shoe.jpg',
                    'price' => 120,
                ],
            ],
        ], 200),
    ]);

    $file = UploadedFile::fake()->image('sneaker.png', 300, 300);

    $this->post('/api/v1/public/taobao/search-by-image', [
        'file' => $file,
        'lang' => 'en',
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.items.0.id', '11223344')
        ->assertJsonPath('data.items.0.title', 'Running Shoes');

    Http::assertSent(function (HttpClientRequest $request): bool {
        return $request->url() === 'https://openapi.elim.asia/v2/products/search-by-image'
            && $request['platform'] === 'taobao'
            && $request['imgId'] === 'uploaded-img-999';
    });
});

it('rejects search-by-image when neither imgUrl, imgId, nor file is provided', function () {
    Http::fake();

    $this->postJson('/api/v1/public/taobao/search-by-image', [
        'lang' => 'en',
    ])->assertStatus(422);

    Http::assertNothingSent();
});
