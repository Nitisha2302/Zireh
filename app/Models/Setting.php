<?php

namespace App\Models;

use App\Helpers\SettingHelper;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function setValue(string $key, mixed $value): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value !== null ? (string) $value : null],
        );

        SettingHelper::clearCache();

        return $setting;
    }
}
