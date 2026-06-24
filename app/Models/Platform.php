<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Platform extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'is_available'
    ];

    public array $translatable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_available' => 'boolean',
        ];
    }
}
