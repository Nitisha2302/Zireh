<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PlatformSlider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'heading',
        'link',
        'image',
    ];

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class, 'platform_slider_platform')->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCatalogCache());
        static::deleted(fn () => static::clearCatalogCache());
        static::restored(fn () => static::clearCatalogCache());
    }

    public static function clearCatalogCache(): void
    {
        foreach (array_keys(config('localization.supported', ['en' => 'English'])) as $locale) {
            Cache::forget("api:platform-catalog:platforms:{$locale}");
            Cache::forget("api:platform-catalog:sliders:{$locale}");
        }
    }
}
