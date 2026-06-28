<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class PlatformCategory extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'platform_id',
        'name',
        'keyword',
        'is_active',
    ];

    public array $translatable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    protected static function booted(): void
    {
        $clearCache = function (PlatformCategory $category): void {
            $code = $category->platform()->value('code');

            if ($code) {
                app(\App\Services\PlatformCategoryService::class)->clearCache($code);
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
        static::restored($clearCache);
    }
}
