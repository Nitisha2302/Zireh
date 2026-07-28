<?php

use App\Support\RapidApi\JdToElimMapper;

it('maps jd search envelope to elim list shape', function () {
    $mapper = new JdToElimMapper();

    $mapped = $mapper->toSearchResponse([
        'ok' => true,
        'count' => 1,
        'data' => [[
            'itemId' => '100256400499',
            'productTitle' => 'HUAWEI Pura 90 Pro',
            'coverUrl' => 'https://img.example/cover.jpg',
            'price' => 6499,
            'itemUrl' => 'https://item.jd.com/100256400499.html',
            'shopName' => '华为京东自营旗舰店',
            'sellerType' => 1,
            'isJdSelf' => true,
            'salesText' => '超千人购买',
            '_page' => 1,
            '_totalCount' => 4628,
        ]],
    ]);

    expect($mapped['paginate']['total'])->toBe(4628)
        ->and($mapped['paginate']['current'])->toBe(1)
        ->and($mapped['items'][0]['id'])->toBe('100256400499')
        ->and($mapped['items'][0]['title'])->toBe('HUAWEI Pura 90 Pro')
        ->and($mapped['items'][0]['img_url'])->toBe('https://img.example/cover.jpg')
        ->and($mapped['items'][0]['price'])->toBe(6499.0)
        ->and($mapped['items'][0]['link'])->toBe('https://item.jd.com/100256400499.html');
});

it('maps jd detail and price into elim detail shape with synthesized sku', function () {
    $mapper = new JdToElimMapper();

    $mapped = $mapper->toDetailResponse(
        [
            'itemId' => '100256400499',
            'productTitle' => 'HUAWEI Pura 90 Pro',
            'coverUrl' => 'https://img.example/cover.jpg',
            'priceMasked' => true,
            'priceDisplay' => '6??9',
            'stockQuantity' => '10',
            'shopId' => '1000004259',
            'shopName' => null,
            'color' => '桑果黑',
            'size' => '16GB+512GB',
            'categoryIds' => ['9987', '653', '655'],
            'itemUrl' => 'https://item.jd.com/100256400499.html',
        ],
        [
            'itemId' => '100256400499',
            'price' => 6499,
            'currency' => 'CNY',
            'available' => true,
        ]
    );

    expect($mapped['id'])->toBe('100256400499')
        ->and($mapped['mp_id'])->toBe('100256400499')
        ->and($mapped['price'])->toBe(6499.0)
        ->and($mapped['quantity'])->toBe(10)
        ->and($mapped['img_urls'])->toBe(['https://img.example/cover.jpg'])
        ->and($mapped['category_id'])->toBe('655')
        ->and($mapped['skus'])->toHaveCount(1)
        ->and($mapped['skus'][0]['id'])->toBe('100256400499')
        ->and($mapped['skus'][0]['properties']['color'])->toBe('桑果黑')
        ->and($mapped['attributes'])->toHaveCount(2);
});
