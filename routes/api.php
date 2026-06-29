<?php

use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAddressController;
use App\Http\Controllers\Api\V1\Elim\Alibaba1688CatalogController;
use App\Http\Controllers\Api\V1\Elim\TaobaoCatalogController;
use App\Http\Controllers\Api\V1\PlatformCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('platforms', [PlatformCatalogController::class, 'platforms']);
    Route::get('platforms/{platform}/commission-slabs', [PlatformCatalogController::class, 'commissionSlabs']);
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
        Route::prefix('register')->group(function () {
            Route::post('otp/send', [CustomerAuthController::class, 'sendRegistrationOtp']);
            Route::post('otp/verify', [CustomerAuthController::class, 'verifyRegistrationOtp']);
            Route::post('complete', [CustomerAuthController::class, 'completeRegistration']);
        });

        Route::prefix('login')->group(function () {
            Route::post('otp/send', [CustomerAuthController::class, 'sendLoginOtp']);
            Route::post('otp/verify', [CustomerAuthController::class, 'verifyLoginOtp']);
        });

        Route::post('otp/resend', [CustomerAuthController::class, 'resendOtp']);

        Route::middleware(['auth:sanctum', 'customer.active'])->group(function () {
            Route::get('me', [CustomerAuthController::class, 'me']);
            Route::match(['put', 'patch'], 'profile', [CustomerAuthController::class, 'updateProfile']);
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            Route::patch('language', [CustomerAuthController::class, 'updateLanguage']);

            Route::get('addresses', [UserAddressController::class, 'index']);
            Route::post('addresses', [UserAddressController::class, 'store']);
            Route::match(['put', 'patch'], 'addresses/{address}', [UserAddressController::class, 'update']);
            Route::delete('addresses/{address}', [UserAddressController::class, 'destroy']);
            Route::post('addresses/{address}/default', [UserAddressController::class, 'setDefault']);
        });
    });
});
