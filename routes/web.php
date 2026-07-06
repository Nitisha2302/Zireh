<?php

use App\Http\Controllers\PublicController;
use App\Livewire\Admin\Customer\{CustomerAddressCreatePage, CustomerAddressEditPage, CustomerAddressListPage, CustomerEditPage, CustomerListPage};
use App\Livewire\Admin\Order\{OrderDetailPage, OrderListPage};
use App\Livewire\Admin\OrderStatus\{OrderStatusCreatePage, OrderStatusEditPage, OrderStatusListPage};
use App\Livewire\Admin\Wallet\{CustomerWalletPage, WalletTransactionListPage};
use App\Livewire\Admin\ShippingMethod\{ShippingMethodCreatePage, ShippingMethodEditPage, ShippingMethodListPage};
use App\Livewire\Admin\ShippingRate\{ShippingRateCreatePage, ShippingRateEditPage, ShippingRateListPage};
use App\Livewire\Admin\Warehouse\{WarehouseCreatePage, WarehouseEditPage, WarehouseListPage, WarehouseShowPage};
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\Platform\{PlatformListPage, PlatformCreatePage, PlatformEditPage};
use App\Livewire\Admin\PlatformSlider\{PlatformSliderListPage, PlatformSliderCreatePage, PlatformSliderEditPage};
use App\Livewire\Admin\PlatformCommissionSlab\{PlatformCommissionSlabListPage, PlatformCommissionSlabCreatePage, PlatformCommissionSlabEditPage};
use App\Livewire\Admin\PlatformCategory\{PlatformCategoryListPage, PlatformCategoryCreatePage, PlatformCategoryEditPage};
use App\Livewire\Admin\ProfilePage;
use App\Livewire\Admin\Settings\DiditSettingsPage;
use App\Livewire\Admin\Settings\CurrencyExchangeSettingsPage;
use App\Livewire\Admin\Settings\ElimApiSettingsPage;
use App\Livewire\Admin\Settings\ElimWarehouseSettingsPage;
use App\Livewire\Admin\Settings\FileManagerSettingsPage;
use App\Livewire\Admin\Settings\PrivacyTermsSettingsPage;
use App\Livewire\Authenticate\LoginPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'nginx in working';
});

Route::get('privacy-policy', [PublicController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-conditions', [PublicController::class, 'termsConditions'])->name('terms-conditions');
Route::get('delete-account', [PublicController::class, 'deleteAccount'])->name('delete-account');

Route::get('sp/login', LoginPage::class)->name('login');

Route::prefix('admin')->name('admin.')->middleware(['is_auth:admin'])->group(function () {
    Route::get('dashboard', DashboardPage::class)->name('dashboard');
    Route::get('profile', ProfilePage::class)->name('profile');

    Route::get('settings/didit', DiditSettingsPage::class)->name('settings.didit');
    Route::get('settings/file-manager', FileManagerSettingsPage::class)->name('settings.file-manager');
    Route::get('settings/currency-exchange', CurrencyExchangeSettingsPage::class)->name('settings.currency-exchange');
    Route::get('settings/elim-api', ElimApiSettingsPage::class)->name('settings.elim-api');
    Route::get('settings/elim-warehouse', ElimWarehouseSettingsPage::class)->name('settings.elim-warehouse');
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
    Route::get('customers/{customer}/addresses', CustomerAddressListPage::class)->name('customers.addresses.index');
    Route::get('customers/{customer}/addresses/create', CustomerAddressCreatePage::class)->name('customers.addresses.create');
    Route::get('customers/{customer}/addresses/{userAddress}/edit', CustomerAddressEditPage::class)->name('customers.addresses.edit');

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
});
