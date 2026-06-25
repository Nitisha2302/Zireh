<?php

use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Seller\DashboardController;
use App\Http\Controllers\Api\V1\Seller\MenuController;
use App\Http\Controllers\Api\V1\Seller\ProductController;
use App\Http\Controllers\Api\V1\Seller\RestaurantBusinessHourController;
use App\Http\Controllers\Api\V1\Seller\SellerAuthController;
use App\Http\Controllers\Api\V1\PlatformCatalogController;
use App\Http\Middleware\TrackCustomerApiActivity;
use App\Http\Middleware\TrackSellerApiActivity;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('platforms', [PlatformCatalogController::class, 'platforms']);
    Route::get('platform-sliders', [PlatformCatalogController::class, 'sliders']);
});
