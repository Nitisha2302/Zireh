<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
