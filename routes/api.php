<?php

use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Seller\DashboardController;
use App\Http\Controllers\Api\V1\Seller\MenuController;
use App\Http\Controllers\Api\V1\Seller\ProductController;
use App\Http\Controllers\Api\V1\Seller\RestaurantBusinessHourController;
use App\Http\Controllers\Api\V1\Seller\SellerAuthController;
use App\Http\Middleware\TrackCustomerApiActivity;
use App\Http\Middleware\TrackSellerApiActivity;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [CustomerAuthController::class, 'register']);
        Route::post('login', [CustomerAuthController::class, 'login']);
        Route::post('otp/send', [CustomerAuthController::class, 'sendOtp']);
        Route::post('otp/verify', [CustomerAuthController::class, 'verifyOtp']);
        Route::get('social/{provider}/redirect', [CustomerAuthController::class, 'socialRedirect'])
            ->whereIn('provider', ['google', 'apple']);
        Route::get('social/{provider}/callback', [CustomerAuthController::class, 'socialCallback'])
            ->whereIn('provider', ['google', 'apple']);

        Route::middleware(['auth:sanctum', TrackCustomerApiActivity::class])->group(function () {
            Route::get('me', [CustomerAuthController::class, 'me']);
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            Route::post('language', [CustomerAuthController::class, 'updateLanguage']);
        });
    });
});

Route::prefix('v1/seller')->group(function () {
    Route::any('verification/didit/webhook', [SellerAuthController::class, 'diditWebhook']);

    Route::prefix('auth')->group(function () {
        Route::post('is-register', [SellerAuthController::class, 'isRegistered']);
        Route::post('otp/send', [SellerAuthController::class, 'sendOtp']);
        Route::post('otp/verify', [SellerAuthController::class, 'verifyOtp']);

        Route::middleware(['auth:sanctum', TrackSellerApiActivity::class])->group(function () {
            Route::post('mail-address', [SellerAuthController::class, 'updateMailAddress']);
            Route::get('me', [SellerAuthController::class, 'me']);
            // Route::get('status', [SellerAuthController::class, 'status']);
            Route::post('me/update', [SellerAuthController::class, 'update']);
            Route::post('delete/account', [SellerAuthController::class, 'deleteAccount']);
            Route::post('logout', [SellerAuthController::class, 'logout']);
            Route::post('restaurant', [SellerAuthController::class, 'saveRestaurantDetails']);
            Route::get('restaurant', [SellerAuthController::class, 'restaurantDetails']);
            Route::get('restaurant/business-hours', [RestaurantBusinessHourController::class, 'index']);
            Route::put('restaurant/business-hours', [RestaurantBusinessHourController::class, 'updateWeek']);
            Route::patch('restaurant/business-hours/{dayOfWeek}', [RestaurantBusinessHourController::class, 'updateDay'])
                ->where('dayOfWeek', '[1-7]');
            Route::post('language', [SellerAuthController::class, 'updateLanguage']);
            Route::post('documents', [SellerAuthController::class, 'uploadDocuments']);
            Route::get('documents', [SellerAuthController::class, 'uploadDocumentsList']);
            Route::post('verification/session', [SellerAuthController::class, 'verificationSession']);
            Route::get('verification/status', [SellerAuthController::class, 'verificationStatus']);

            // Cuisine management
            Route::get('cuisines', [DashboardController::class, 'listCuisines']);
            Route::get('categories', [DashboardController::class, 'listCategories']);

            Route::get('products', [ProductController::class, 'index']);
            Route::post('products', [ProductController::class, 'store']);
            Route::get('products/{product}', [ProductController::class, 'show']);
            Route::post('products/{product}', [ProductController::class, 'update']);
            Route::patch('products/{product}/availability', [ProductController::class, 'updateAvailability']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);

            Route::get('menus', [MenuController::class, 'index']);
            Route::post('menus', [MenuController::class, 'store']);
            Route::get('menus/{menu}', [MenuController::class, 'show']);
            Route::post('menus/{menu}', [MenuController::class, 'update']);
            Route::delete('menus/{menu}', [MenuController::class, 'destroy']);
        });
    });
});
