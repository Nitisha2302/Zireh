<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWishlistItem extends Model
{
    public const PLATFORM_TAOBAO = 'taobao';

    public const PLATFORM_1688 = '1688';

    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'product_snapshot',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
