<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Platform extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
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
}
