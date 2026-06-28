<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    public const TYPE_HOME = 'home';

    public const TYPE_WORK = 'work';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'alternate_phone',
        'address_line_1',
        'address_line_2',
        'landmark',
        'city',
        'state',
        'country',
        'postal_code',
        'address_type',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
