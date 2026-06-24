<?php

namespace App\Livewire\Admin\Settings;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use App\Services\FileManager;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin', ['title' => 'File Manager Settings'])]
class FileManagerSettingsPage extends Component
{
    public string $file_upload_disk = 'local';
    public string $file_s3_key = '';
    public string $file_s3_secret = '';
    public string $file_s3_region = '';
    public string $file_s3_bucket = '';
    public string $file_s3_url = '';
    public string $file_s3_endpoint = '';
    public bool $file_s3_use_path_style_endpoint = false;

    public function mount(): void
    {
        $this->file_upload_disk = (string) SettingHelper::get('file_upload_disk', 'local');
        $this->file_s3_key = (string) SettingHelper::get('file_s3_key', config('filesystems.disks.s3.key', ''));
        $this->file_s3_secret = (string) SettingHelper::get('file_s3_secret', config('filesystems.disks.s3.secret', ''));
        $this->file_s3_region = (string) SettingHelper::get('file_s3_region', config('filesystems.disks.s3.region', ''));
        $this->file_s3_bucket = (string) SettingHelper::get('file_s3_bucket', config('filesystems.disks.s3.bucket', ''));
        $this->file_s3_url = (string) SettingHelper::get('file_s3_url', config('filesystems.disks.s3.url', ''));
        $this->file_s3_endpoint = (string) SettingHelper::get('file_s3_endpoint', config('filesystems.disks.s3.endpoint', ''));
        $this->file_s3_use_path_style_endpoint = filter_var(
            SettingHelper::get('file_s3_use_path_style_endpoint', config('filesystems.disks.s3.use_path_style_endpoint', false)),
            FILTER_VALIDATE_BOOL
        );
    }

    public function save(): void
    {
        $rules = [
            'file_upload_disk' => ['required', 'in:local,s3'],
            'file_s3_url' => ['nullable', 'url'],
            'file_s3_endpoint' => ['nullable', 'url'],
        ];

        if ($this->file_upload_disk === 's3') {
            $rules = array_merge($rules, [
                'file_s3_key' => ['required', 'string'],
                'file_s3_secret' => ['required', 'string'],
                'file_s3_region' => ['required', 'string'],
                'file_s3_bucket' => ['required', 'string'],
            ]);
        }

        $this->validate($rules);

        foreach ([
            'file_upload_disk' => $this->file_upload_disk,
            'file_s3_key' => $this->file_s3_key,
            'file_s3_secret' => $this->file_s3_secret,
            'file_s3_region' => $this->file_s3_region,
            'file_s3_bucket' => $this->file_s3_bucket,
            'file_s3_url' => $this->file_s3_url,
            'file_s3_endpoint' => $this->file_s3_endpoint,
            'file_s3_use_path_style_endpoint' => $this->file_s3_use_path_style_endpoint ? '1' : '0',
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        SettingHelper::clearCache();
        app(FileManager::class)->clearCache();
    }

    public function render()
    {
        return view('livewire.admin.settings.file-manager-settings-page')
            ->title(__('admin.file_manager_settings'));
    }
}
