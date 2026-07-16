<?php

use App\Http\Controllers\Api\V1\ChinaWarehouseController;
use App\Http\Controllers\Api\V1\Content\LegalContentController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\Elim\Alibaba1688CatalogController;
use App\Http\Controllers\Api\V1\Elim\TaobaoCatalogController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\PlatformCatalogController;
use App\Http\Controllers\Api\V1\ShippingController;
use Illuminate\Support\Facades\Route;

return function (): void {
    Route::get('platforms', [PlatformCatalogController::class, 'platforms']);
    Route::get('platforms/{platform}/commission-slabs', [PlatformCatalogController::class, 'commissionSlabs']);
    Route::get('platform-sliders', [PlatformCatalogController::class, 'sliders']);

    Route::get('lessons', [ContentController::class, 'lessons']);
    Route::get('news', [ContentController::class, 'news']);
    Route::get('legal', [LegalContentController::class, 'index']);
    
    Route::get('china-warehouse', [ChinaWarehouseController::class, 'show']);

    Route::get('shipping/methods', [ShippingController::class, 'methods']);
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    Route::get('order-statuses', [OrderStatusController::class, 'index']);

    Route::prefix('taobao')->group(function () {
        Route::get('products', [TaobaoCatalogController::class, 'products']);
        Route::get('search', [TaobaoCatalogController::class, 'search']);
        Route::get('products/{id}', [TaobaoCatalogController::class, 'show']);
        Route::get('categories', [TaobaoCatalogController::class, 'categories']);
        Route::post('image-search', [TaobaoCatalogController::class, 'imageSearch']);
        Route::post('upload-image', [TaobaoCatalogController::class, 'uploadImage']);
    });

    Route::prefix('1688')->group(function () {
        Route::get('products', [Alibaba1688CatalogController::class, 'products']);
        Route::get('search', [Alibaba1688CatalogController::class, 'search']);
        Route::get('products/{id}', [Alibaba1688CatalogController::class, 'show']);
        Route::get('categories', [Alibaba1688CatalogController::class, 'categories']);
        Route::post('image-search', [Alibaba1688CatalogController::class, 'imageSearch']);
        Route::post('upload-image', [Alibaba1688CatalogController::class, 'uploadImage']);
    });
};
