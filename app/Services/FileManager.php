<?php

namespace App\Services;

use App\Helpers\SettingHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FileManager
{
    public const DYNAMIC_S3_DISK = 'dynamic_s3';

    public const CONFIG_CACHE_KEY = 'file_manager.disk_config';

    public function store(?UploadedFile $file, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        return $this->disk()->putFile($directory, $file, ['visibility' => 'public']);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str($path)->startsWith(['http://', 'https://'])) {
            return $path;
        }

        return $this->disk()->url($path);
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $this->disk()->delete($path);
    }

    public function disk()
    {
        $config = $this->configuration();

        if (($config['driver'] ?? 'local') === 's3') {
            config([
                'filesystems.disks.' . self::DYNAMIC_S3_DISK => $config,
            ]);

            return Storage::disk(self::DYNAMIC_S3_DISK);
        }

        return Storage::disk('public');
    }

    public function configuration(): array
    {
        return Cache::rememberForever(self::CONFIG_CACHE_KEY, fn(): array => $this->resolveConfiguration());
    }

    public function clearCache(): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY);
    }

    protected function resolveConfiguration(): array
    {
        $driver = SettingHelper::get('file_upload_disk', 'local');

        if ($driver === 's3') {
            return [
                'driver' => 's3',
                'key' => SettingHelper::get('file_s3_key', config('filesystems.disks.s3.key')),
                'secret' => SettingHelper::get('file_s3_secret', config('filesystems.disks.s3.secret')),
                'region' => SettingHelper::get('file_s3_region', config('filesystems.disks.s3.region')),
                'bucket' => SettingHelper::get('file_s3_bucket', config('filesystems.disks.s3.bucket')),
                'url' => SettingHelper::get('file_s3_url', config('filesystems.disks.s3.url')),
                'endpoint' => SettingHelper::get('file_s3_endpoint', config('filesystems.disks.s3.endpoint')),
                'use_path_style_endpoint' => filter_var(
                    SettingHelper::get('file_s3_use_path_style_endpoint', config('filesystems.disks.s3.use_path_style_endpoint')),
                    FILTER_VALIDATE_BOOL
                ),
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ];
        }

        return config('filesystems.disks.public');
    }
}
