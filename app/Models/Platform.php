<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class Platform extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'logo',
        'is_available',
    ];

    public array $translatable = [
        'name',
        'description',
        'logo',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function sliders(): BelongsToMany
    {
        return $this->belongsToMany(PlatformSlider::class, 'platform_slider_platform')->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(PlatformCategory::class);
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
