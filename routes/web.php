<?php

use App\Http\Controllers\PublicController;
use App\Livewire\Admin\Customer\CustomerEditPage;
use App\Livewire\Admin\Customer\CustomerListPage;
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\Lesson\LessonCreatePage;
use App\Livewire\Admin\Lesson\LessonEditPage;
use App\Livewire\Admin\Lesson\LessonListPage;
use App\Livewire\Admin\News\NewsCreatePage;
use App\Livewire\Admin\News\NewsEditPage;
use App\Livewire\Admin\News\NewsListPage;
use App\Livewire\Admin\Order\OrderDetailPage;
use App\Livewire\Admin\Order\OrderListPage;
use App\Livewire\Admin\OrderStatus\OrderStatusCreatePage;
use App\Livewire\Admin\OrderStatus\OrderStatusEditPage;
use App\Livewire\Admin\OrderStatus\OrderStatusListPage;
use App\Livewire\Admin\Platform\PlatformCreatePage;
use App\Livewire\Admin\Platform\PlatformEditPage;
use App\Livewire\Admin\Platform\PlatformListPage;
use App\Livewire\Admin\PlatformCategory\PlatformCategoryCreatePage;
use App\Livewire\Admin\PlatformCategory\PlatformCategoryEditPage;
use App\Livewire\Admin\PlatformCategory\PlatformCategoryListPage;
use App\Livewire\Admin\PlatformCommissionSlab\PlatformCommissionSlabCreatePage;
use App\Livewire\Admin\PlatformCommissionSlab\PlatformCommissionSlabEditPage;
use App\Livewire\Admin\PlatformCommissionSlab\PlatformCommissionSlabListPage;
use App\Livewire\Admin\PlatformSlider\PlatformSliderCreatePage;
use App\Livewire\Admin\PlatformSlider\PlatformSliderEditPage;
use App\Livewire\Admin\PlatformSlider\PlatformSliderListPage;
use App\Livewire\Admin\ProfilePage;
use App\Livewire\Admin\Settings\AlifApiLogDetailPage;
use App\Livewire\Admin\Settings\AlifApiLogListPage;
use App\Livewire\Admin\Settings\AlifApiSettingsPage;
use App\Livewire\Admin\Settings\ChinaWarehouseLoginSettingsPage;
use App\Livewire\Admin\Settings\CompanySettingsPage;
use App\Livewire\Admin\Settings\CurrencyExchangeSettingsPage;
use App\Livewire\Admin\Settings\DiditSettingsPage;
use App\Livewire\Admin\Settings\ElimApiLogDetailPage;
use App\Livewire\Admin\Settings\ElimApiLogListPage;
use App\Livewire\Admin\Settings\ElimApiSettingsPage;
use App\Livewire\Admin\Settings\ElimWarehouseSettingsPage;
use App\Livewire\Admin\Settings\FileManagerSettingsPage;
use App\Livewire\Admin\Settings\PrivacyTermsSettingsPage;
use App\Livewire\Admin\Settings\RapidApiLogDetailPage;
use App\Livewire\Admin\Settings\RapidApiLogListPage;
use App\Livewire\Admin\ShippingMethod\ShippingMethodCreatePage;
use App\Livewire\Admin\ShippingMethod\ShippingMethodEditPage;
use App\Livewire\Admin\ShippingMethod\ShippingMethodListPage;
use App\Livewire\Admin\ShippingRate\ShippingRateCreatePage;
use App\Livewire\Admin\ShippingRate\ShippingRateEditPage;
use App\Livewire\Admin\ShippingRate\ShippingRateListPage;
use App\Livewire\Admin\Wallet\CustomerWalletPage;
use App\Livewire\Admin\Wallet\WalletTransactionListPage;
use App\Livewire\Admin\Warehouse\WarehouseCreatePage;
use App\Livewire\Admin\Warehouse\WarehouseEditPage;
use App\Livewire\Admin\Warehouse\WarehouseListPage;
use App\Livewire\Admin\Warehouse\WarehouseShowPage;
use App\Livewire\Authenticate\LoginPage;
use App\Livewire\Warehouse\China\LoginPage as ChinaLoginPage;
use App\Livewire\Warehouse\China\OrderDetailPage as ChinaOrderDetailPage;
use App\Livewire\Warehouse\China\OrderListPage as ChinaOrderListPage;
use App\Livewire\Warehouse\China\ProfilePage as ChinaProfilePage;
use App\Livewire\Warehouse\Tajikistan\LoginPage as TajikistanLoginPage;
use App\Livewire\Warehouse\Tajikistan\OrderDetailPage as TajikistanOrderDetailPage;
use App\Livewire\Warehouse\Tajikistan\OrderListPage as TajikistanOrderListPage;
use App\Livewire\Warehouse\Tajikistan\PickupPage as TajikistanPickupPage;
use App\Livewire\Warehouse\Tajikistan\ProfilePage as TajikistanProfilePage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.landing');
});

Route::get('privacy-policy', [PublicController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-conditions', [PublicController::class, 'termsConditions'])->name('terms-conditions');
Route::get('payment/alif/return', [PublicController::class, 'alifReturn'])->name('payment.alif.return');

Route::get('sp/login', LoginPage::class)->name('login');

Route::get('china/login', ChinaLoginPage::class)->name('china.login');

Route::prefix('china')->name('china.')->middleware(['is_auth:admin', 'admin.role:china_warehouse'])->group(function () {
    Route::get('profile', ChinaProfilePage::class)->name('profile');
    Route::get('orders', ChinaOrderListPage::class)->name('orders.index');
    Route::get('orders/{order}', ChinaOrderDetailPage::class)->name('orders.show');
});

Route::get('tajikistan/login', TajikistanLoginPage::class)->name('tajikistan.login');

Route::prefix('tajikistan')->name('tajikistan.')->middleware(['is_auth:admin', 'admin.role:tajikistan_warehouse'])->group(function () {
    Route::get('profile', TajikistanProfilePage::class)->name('profile');
    Route::get('orders', TajikistanOrderListPage::class)->name('orders.index');
    Route::get('orders/{order}', TajikistanOrderDetailPage::class)->name('orders.show');
    Route::get('pickup', TajikistanPickupPage::class)->name('pickup.index');
    Route::get('pickup/{token}', TajikistanPickupPage::class)->name('pickup.show');
});

Route::prefix('admin')->name('admin.')->middleware(['is_auth:admin'])->group(function () {
    Route::middleware(['admin.role:super_admin'])->group(function () {
        Route::get('dashboard', DashboardPage::class)->name('dashboard');
        Route::get('profile', ProfilePage::class)->name('profile');

        Route::get('settings/didit', DiditSettingsPage::class)->name('settings.didit');
        Route::get('settings/file-manager', FileManagerSettingsPage::class)->name('settings.file-manager');
        Route::get('settings/currency-exchange', CurrencyExchangeSettingsPage::class)->name('settings.currency-exchange');
        Route::get('settings/elim-api', ElimApiSettingsPage::class)->name('settings.elim-api');
        Route::get('settings/elim-api/logs', ElimApiLogListPage::class)->name('settings.elim-api-logs.index');
        Route::get('settings/elim-api/logs/{log}', ElimApiLogDetailPage::class)->name('settings.elim-api-logs.show');
        Route::get('settings/rapid-api/logs', RapidApiLogListPage::class)->name('settings.rapid-api-logs.index');
        Route::get('settings/rapid-api/logs/{log}', RapidApiLogDetailPage::class)->name('settings.rapid-api-logs.show');
        Route::get('settings/alif', AlifApiSettingsPage::class)->name('settings.alif');
        Route::get('settings/alif/logs', AlifApiLogListPage::class)->name('settings.alif-api-logs.index');
        Route::get('settings/alif/logs/{log}', AlifApiLogDetailPage::class)->name('settings.alif-api-logs.show');
        Route::get('settings/elim-warehouse', ElimWarehouseSettingsPage::class)->name('settings.elim-warehouse');
        Route::get('settings/china-warehouse-login', ChinaWarehouseLoginSettingsPage::class)->name('settings.china-warehouse-login');
        Route::get('settings/company', CompanySettingsPage::class)->name('settings.company');
        Route::get('settings/privacy-terms', PrivacyTermsSettingsPage::class)->name('settings.privacy-terms');

        Route::get('orders', OrderListPage::class)->name('orders.index');
        Route::get('orders/{order}', OrderDetailPage::class)->name('orders.show');

        Route::get('order-statuses', OrderStatusListPage::class)->name('order-statuses.index');
        Route::get('order-statuses/create', OrderStatusCreatePage::class)->name('order-statuses.create');
        Route::get('order-statuses/{orderStatus}/edit', OrderStatusEditPage::class)->name('order-statuses.edit');

        Route::get('wallet-transactions', WalletTransactionListPage::class)->name('wallet-transactions.index');
        Route::get('customers/{customer}/wallet', CustomerWalletPage::class)->name('customers.wallet');

        Route::get('customers', CustomerListPage::class)->name('customers.index');
        Route::get('customers/{customer}/edit', CustomerEditPage::class)->name('customers.edit');

        Route::get('warehouses', WarehouseListPage::class)->name('warehouses.index');
        Route::get('warehouses/create', WarehouseCreatePage::class)->name('warehouses.create');
        Route::get('warehouses/{warehouse}', WarehouseShowPage::class)->name('warehouses.show');
        Route::get('warehouses/{warehouse}/edit', WarehouseEditPage::class)->name('warehouses.edit');

        Route::get('shipping-methods', ShippingMethodListPage::class)->name('shipping-methods.index');
        Route::get('shipping-methods/create', ShippingMethodCreatePage::class)->name('shipping-methods.create');
        Route::get('shipping-methods/{shippingMethod}/edit', ShippingMethodEditPage::class)->name('shipping-methods.edit');

        Route::get('shipping-rates', ShippingRateListPage::class)->name('shipping-rates.index');
        Route::get('shipping-rates/create', ShippingRateCreatePage::class)->name('shipping-rates.create');
        Route::get('shipping-rates/{shippingRate}/edit', ShippingRateEditPage::class)->name('shipping-rates.edit');

        Route::get('platforms', PlatformListPage::class)->name('platforms.index');
        Route::get('platforms/create', PlatformCreatePage::class)->name('platforms.create');
        Route::get('platforms/{platform}/edit', PlatformEditPage::class)->name('platforms.edit');
        Route::get('platforms/{platform}/commission-slabs', PlatformCommissionSlabListPage::class)->name('platforms.commission-slabs.index');
        Route::get('platforms/{platform}/commission-slabs/create', PlatformCommissionSlabCreatePage::class)->name('platforms.commission-slabs.create');
        Route::get('platforms/{platform}/commission-slabs/{slab}/edit', PlatformCommissionSlabEditPage::class)->name('platforms.commission-slabs.edit');
        Route::get('platform-sliders', PlatformSliderListPage::class)->name('platform-sliders.index');
        Route::get('platform-sliders/create', PlatformSliderCreatePage::class)->name('platform-sliders.create');
        Route::get('platform-sliders/{platformSlider}/edit', PlatformSliderEditPage::class)->name('platform-sliders.edit');
        Route::get('platform-categories', PlatformCategoryListPage::class)->name('platform-categories.index');
        Route::get('platform-categories/create', PlatformCategoryCreatePage::class)->name('platform-categories.create');
        Route::get('platform-categories/{platformCategory}/edit', PlatformCategoryEditPage::class)->name('platform-categories.edit');
        Route::get('lessons', LessonListPage::class)->name('lessons.index');
        Route::get('lessons/create', LessonCreatePage::class)->name('lessons.create');
        Route::get('lessons/{lesson}/edit', LessonEditPage::class)->name('lessons.edit');
        Route::get('news', NewsListPage::class)->name('news.index');
        Route::get('news/create', NewsCreatePage::class)->name('news.create');
        Route::get('news/{news}/edit', NewsEditPage::class)->name('news.edit');
    });
});
