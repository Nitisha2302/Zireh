<?php

use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAddressController;
use App\Http\Controllers\Api\V1\Auth\WalletController;
use App\Http\Controllers\Api\V1\Auth\WishlistController;
use App\Http\Controllers\Api\V1\Cart\Platform1688\Platform1688CartController;
use App\Http\Controllers\Api\V1\Cart\Platform1688\Platform1688CheckoutController;
use App\Http\Controllers\Api\V1\Cart\Taobao\TaobaoCartController;
use App\Http\Controllers\Api\V1\Cart\Taobao\TaobaoCheckoutController;
use App\Http\Controllers\Api\V1\Order\CustomerOrderController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

$publicCatalogRoutes = require __DIR__ . '/api/public-catalog.php';

Route::prefix('v1')->group(function () use ($publicCatalogRoutes) {
    // Guest browsing — no Bearer token required.
    $publicCatalogRoutes();

    // Preferred prefix for mobile guest mode.
    Route::prefix('public')->group($publicCatalogRoutes);

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
            Route::match(['put', 'patch', 'post'], 'profile', [CustomerAuthController::class, 'updateProfile']);
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            Route::delete('account', [CustomerAuthController::class, 'deleteAccount']);
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
