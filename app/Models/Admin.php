<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_CHINA_WAREHOUSE = 'china_warehouse';

    public const ROLE_TAJIKISTAN_WAREHOUSE = 'tajikistan_warehouse';

    protected $fillable = [
        'name',
        'username',
        'avatar',
        'phone',
        'email',
        'role',
        'warehouse_id',
        'is_two_factor',
        'email_verified_at',
        'password',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function loginLogs(): MorphMany
    {
        return $this->morphMany(LoginLog::class, 'authenticatable');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isChinaWarehouseStaff(): bool
    {
        return $this->role === self::ROLE_CHINA_WAREHOUSE;
    }

    public function isTajikistanWarehouseStaff(): bool
    {
        return $this->role === self::ROLE_TAJIKISTAN_WAREHOUSE;
    }

    public function canAccessChinaWarehousePanel(): bool
    {
        return $this->isChinaWarehouseStaff();
    }

    public function canAccessTajikistanWarehousePanel(): bool
    {
        return $this->isTajikistanWarehouseStaff();
    }

    public function isWarehouseStaff(): bool
    {
        return $this->isChinaWarehouseStaff() || $this->isTajikistanWarehouseStaff();
    }

    public function warehouseHomeRoute(): string
    {
        return match ($this->role) {
            self::ROLE_CHINA_WAREHOUSE => 'china.orders.index',
            self::ROLE_TAJIKISTAN_WAREHOUSE => 'tajikistan.orders.index',
            default => 'admin.dashboard',
        };
    }

    public function warehouseLoginRoute(): string
    {
        return match ($this->role) {
            self::ROLE_CHINA_WAREHOUSE => 'china.login',
            self::ROLE_TAJIKISTAN_WAREHOUSE => 'tajikistan.login',
            default => 'login',
        };
    }

    public function warehouseProfileRoute(): string
    {
        return match ($this->role) {
            self::ROLE_CHINA_WAREHOUSE => 'china.profile',
            self::ROLE_TAJIKISTAN_WAREHOUSE => 'tajikistan.profile',
            default => 'admin.profile',
        };
    }

    public function defaultPanelRoute(): string
    {
        if ($this->isWarehouseStaff()) {
            return $this->warehouseHomeRoute();
        }

        return 'admin.dashboard';
    }

    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => __('admin.role_super_admin'),
            self::ROLE_CHINA_WAREHOUSE => __('admin.role_china_warehouse'),
            self::ROLE_TAJIKISTAN_WAREHOUSE => __('admin.role_tajikistan_warehouse'),
        ];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_two_factor' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
