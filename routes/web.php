<?php

use App\Http\Controllers\PublicController;
use App\Livewire\Admin\DashboardPage;
use App\Livewire\Admin\Plateform\PlateformListPage;
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

    Route::get('plateforms', PlateformListPage::class)->name('platforms');
});
