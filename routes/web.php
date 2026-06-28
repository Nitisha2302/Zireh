<?php

use App\Http\Controllers\PublicController;
use App\Livewire\Admin\Customer\{CustomerAddressCreatePage, CustomerAddressEditPage, CustomerAddressListPage, CustomerEditPage, CustomerListPage};
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\Platform\{PlatformListPage, PlatformCreatePage, PlatformEditPage};
use App\Livewire\Admin\PlatformSlider\{PlatformSliderListPage, PlatformSliderCreatePage, PlatformSliderEditPage};
use App\Livewire\Admin\PlatformCategory\{PlatformCategoryListPage, PlatformCategoryCreatePage, PlatformCategoryEditPage};
use App\Livewire\Admin\ProfilePage;
use App\Livewire\Admin\Settings\DiditSettingsPage;
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
    Route::get('settings/privacy-terms', PrivacyTermsSettingsPage::class)->name('settings.privacy-terms');

    Route::get('customers', CustomerListPage::class)->name('customers.index');
    Route::get('customers/{customer}/edit', CustomerEditPage::class)->name('customers.edit');
    Route::get('customers/{customer}/addresses', CustomerAddressListPage::class)->name('customers.addresses.index');
    Route::get('customers/{customer}/addresses/create', CustomerAddressCreatePage::class)->name('customers.addresses.create');
    Route::get('customers/{customer}/addresses/{userAddress}/edit', CustomerAddressEditPage::class)->name('customers.addresses.edit');

    Route::get('platforms', PlatformListPage::class)->name('platforms.index');
    Route::get('platforms/create', PlatformCreatePage::class)->name('platforms.create');
    Route::get('platforms/{plateform}/edit', PlatformEditPage::class)->name('platforms.edit');
    Route::get('platform-sliders', PlatformSliderListPage::class)->name('platform-sliders.index');
    Route::get('platform-sliders/create', PlatformSliderCreatePage::class)->name('platform-sliders.create');
    Route::get('platform-sliders/{platformSlider}/edit', PlatformSliderEditPage::class)->name('platform-sliders.edit');
    Route::get('platform-categories', PlatformCategoryListPage::class)->name('platform-categories.index');
    Route::get('platform-categories/create', PlatformCategoryCreatePage::class)->name('platform-categories.create');
    Route::get('platform-categories/{platformCategory}/edit', PlatformCategoryEditPage::class)->name('platform-categories.edit');
});
