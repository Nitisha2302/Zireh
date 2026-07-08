<?php

use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAddressController;
use App\Http\Controllers\Api\V1\Auth\WalletController;
use App\Http\Controllers\Api\V1\Auth\WishlistController;
use App\Http\Controllers\Api\V1\Cart\Platform1688\Platform1688CartController;
use App\Http\Controllers\Api\V1\Cart\Platform1688\Platform1688CheckoutController;
use App\Http\Controllers\Api\V1\Cart\Taobao\TaobaoCartController;
use App\Http\Controllers\Api\V1\Cart\Taobao\TaobaoCheckoutController;
use App\Http\Controllers\Api\V1\Elim\Alibaba1688CatalogController;
use App\Http\Controllers\Api\V1\Elim\TaobaoCatalogController;
use App\Http\Controllers\Api\V1\Order\CustomerOrderController;
use App\Http\Controllers\Api\V1\OrderStatusController;
use App\Http\Controllers\Api\V1\PlatformCatalogController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('platforms', [PlatformCatalogController::class, 'platforms']);
    Route::get('platforms/{platform}/commission-slabs', [PlatformCatalogController::class, 'commissionSlabs']);
    Route::get('platform-sliders', [PlatformCatalogController::class, 'sliders']);

    Route::get('shipping/methods', [ShippingController::class, 'methods']);
    Route::post('shipping/calculate', [ShippingController::class, 'calculate']);

    Route::get('order-statuses', [OrderStatusController::class, 'index']);

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

            Route::get('warehouses', [WarehouseController::class, 'index']);

            Route::get('wishlist', [WishlistController::class, 'index']);
            Route::post('wishlist', [WishlistController::class, 'store']);
            Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy']);

            Route::get('wallet', [WalletController::class, 'show']);
            Route::post('wallet/deposit', [WalletController::class, 'deposit']);
            Route::get('wallet/transactions', [WalletController::class, 'transactions']);

            Route::get('orders/elim/purchasing-wallet', [CustomerOrderController::class, 'elimPurchasingWallet']);
            Route::get('orders/elim/exchange-rates', [CustomerOrderController::class, 'elimExchangeRates']);
            Route::get('orders/{order}/pickup', [CustomerOrderController::class, 'pickup']);
            Route::post('orders/{order}/pickup/pay', [CustomerOrderController::class, 'payPickup']);
            Route::get('orders/{order}/payment-preview', [CustomerOrderController::class, 'paymentPreview']);
            Route::post('orders/{order}/pay', [CustomerOrderController::class, 'pay']);
            Route::post('orders/{order}/sync', [CustomerOrderController::class, 'sync']);
            Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
            Route::get('orders/{order}/logistics', [CustomerOrderController::class, 'logistics']);

            Route::prefix('taobao')->group(function () {
                Route::get('cart', [TaobaoCartController::class, 'index']);
                Route::post('cart/items', [TaobaoCartController::class, 'store']);
                Route::patch('cart/items/{cartItem}', [TaobaoCartController::class, 'update']);
                Route::delete('cart/items/{cartItem}', [TaobaoCartController::class, 'destroy']);
                Route::delete('cart', [TaobaoCartController::class, 'clear']);
                Route::post('cart/preview', [TaobaoCheckoutController::class, 'preview']);
                Route::post('cart/checkout', [TaobaoCheckoutController::class, 'checkout']);
                Route::get('orders', [TaobaoCheckoutController::class, 'orders']);
                Route::get('orders/{order}', [TaobaoCheckoutController::class, 'show']);
            });

            Route::prefix('1688')->group(function () {
                Route::get('cart', [Platform1688CartController::class, 'index']);
                Route::post('cart/items', [Platform1688CartController::class, 'store']);
                Route::patch('cart/items/{cartItem}', [Platform1688CartController::class, 'update']);
                Route::delete('cart/items/{cartItem}', [Platform1688CartController::class, 'destroy']);
                Route::delete('cart', [Platform1688CartController::class, 'clear']);
                Route::post('cart/preview', [Platform1688CheckoutController::class, 'preview']);
                Route::post('cart/checkout', [Platform1688CheckoutController::class, 'checkout']);
                Route::get('orders', [Platform1688CheckoutController::class, 'orders']);
                Route::get('orders/{order}', [Platform1688CheckoutController::class, 'show']);
            });
        });
    });
});
