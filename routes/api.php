<?php

use App\Http\Controllers\Api\V1\Elim\Alibaba1688CatalogController;
use App\Http\Controllers\Api\V1\Elim\TaobaoCatalogController;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\PlatformCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('platforms', [PlatformCatalogController::class, 'platforms']);
    Route::get('platform-sliders', [PlatformCatalogController::class, 'sliders']);

    Route::prefix('taobao')->name('api.taobao.')->group(function () {
        Route::get('products', [TaobaoCatalogController::class, 'products'])->name('products.index');
        Route::get('search', [TaobaoCatalogController::class, 'search'])->name('products.search');
        Route::get('products/{id}', [TaobaoCatalogController::class, 'show'])->name('products.show');
        Route::get('categories', [TaobaoCatalogController::class, 'categories'])->name('categories.index');
        Route::post('image-search', [TaobaoCatalogController::class, 'imageSearch'])->name('products.image-search');
        Route::post('upload-image', [TaobaoCatalogController::class, 'uploadImage'])->name('products.upload-image');
    });

    Route::prefix('1688')->name('api.1688.')->group(function () {
        Route::get('products', [Alibaba1688CatalogController::class, 'products'])->name('products.index');
        Route::get('search', [Alibaba1688CatalogController::class, 'search'])->name('products.search');
        Route::get('products/{id}', [Alibaba1688CatalogController::class, 'show'])->name('products.show');
        Route::get('categories', [Alibaba1688CatalogController::class, 'categories'])->name('categories.index');
        Route::post('image-search', [Alibaba1688CatalogController::class, 'imageSearch'])->name('products.image-search');
        Route::post('upload-image', [Alibaba1688CatalogController::class, 'uploadImage'])->name('products.upload-image');
    });

    Route::prefix('auth')->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login', [CustomerAuthController::class, 'login']);
        Route::post('otp/send', [CustomerAuthController::class, 'sendOtp']);
        Route::post('otp/verify', [CustomerAuthController::class, 'verifyOtp']);
    });
});
