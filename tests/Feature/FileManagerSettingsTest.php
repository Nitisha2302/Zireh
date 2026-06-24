<?php

use App\Helpers\SettingHelper;
use App\Livewire\Admin\Settings\FileManagerSettingsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Services\FileManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSettingsAdmin(array $attributes = []): Admin
{
    return Admin::create(array_merge([
        'name' => 'Settings Admin',
        'username' => 'settingsadmin',
        'email' => 'settings@example.com',
        'password' => Hash::make('secret-password'),
        'email_verified_at' => now(),
    ], $attributes));
}

it('shows the file manager settings page to authenticated admins', function () {
    $admin = makeSettingsAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.file-manager'))
        ->assertOk()
        ->assertSee('File Manager Settings');
});

it('saves file manager settings and clears cached configuration', function () {
    $admin = makeSettingsAdmin();

    Setting::create(['key' => 'file_upload_disk', 'value' => 'local']);

    expect(SettingHelper::get('file_upload_disk'))->toBe('local');

    $this->actingAs($admin, 'admin');

    Livewire::test(FileManagerSettingsPage::class)
        ->set('file_upload_disk', 's3')
        ->set('file_s3_key', 'key-123')
        ->set('file_s3_secret', 'secret-123')
        ->set('file_s3_region', 'ap-south-1')
        ->set('file_s3_bucket', 'restro-bucket')
        ->set('file_s3_url', 'https://cdn.example.com')
        ->set('file_s3_endpoint', 'https://s3.ap-south-1.amazonaws.com')
        ->set('file_s3_use_path_style_endpoint', true)
        ->call('save');

    app(FileManager::class)->clearCache();

    expect(SettingHelper::get('file_upload_disk'))->toBe('s3')
        ->and(Setting::query()->where('key', 'file_s3_bucket')->value('value'))->toBe('restro-bucket')
        ->and(app(FileManager::class)->configuration()['driver'])->toBe('s3');
});
