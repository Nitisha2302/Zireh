<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCartItem extends Model
{
    public const PLATFORM_TAOBAO = 'taobao';

    public const PLATFORM_1688 = '1688';

    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'marketplace_id',
        'sku_id',
        'quantity',
        'unit_price',
        'product_snapshot',
        'selected_attributes',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'product_snapshot' => 'array',
            'selected_attributes' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineSubtotal(): float
    {
        return round((float) $this->unit_price * $this->quantity, 2);
    }
}
