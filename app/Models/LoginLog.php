<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class LoginLog extends Model
{
    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'guard',
        'login',
        'ip_address',
        'user_agent',
        'session_id',
        'access_token_id',
        'login_at',
        'logout_at',
        'last_seen_at',
        'last_activity_url',
        'successful',
        'failure_reason',
    ];

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function recordSuccess(
        Model $authenticatable,
        string $guard,
        string $login,
        Request $request,
        ?int $accessTokenId = null
    ): self {
        return self::create([
            'authenticatable_type' => $authenticatable::class,
            'authenticatable_id' => $authenticatable->getKey(),
            'guard' => $guard,
            'login' => $login,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => self::sessionId($request),
            'access_token_id' => $accessTokenId,
            'login_at' => now(),
            'last_seen_at' => now(),
            'last_activity_url' => $request->fullUrl(),
            'successful' => true,
        ]);
    }

    public static function recordFailure(string $guard, string $login, Request $request, ?Model $authenticatable = null, ?string $reason = null): self
    {
        return self::create([
            'authenticatable_type' => $authenticatable ? $authenticatable::class : null,
            'authenticatable_id' => $authenticatable?->getKey(),
            'guard' => $guard,
            'login' => $login,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => self::sessionId($request),
            'access_token_id' => self::accessTokenId($authenticatable),
            'login_at' => now(),
            'last_seen_at' => now(),
            'last_activity_url' => $request->fullUrl(),
            'successful' => false,
            'failure_reason' => $reason,
        ]);
    }

    public static function touchCurrentSession(Model $authenticatable, string $guard, Request $request): void
    {
        $query = self::query()
            ->whereMorphedTo('authenticatable', $authenticatable)
            ->where('guard', $guard)
            ->where('successful', true)
            ->whereNull('logout_at');

        self::applyCurrentContext($query, $authenticatable, $request)
            ->latest('login_at')
            ->limit(1)
            ->update([
                'last_seen_at' => now(),
                'last_activity_url' => $request->fullUrl(),
            ]);
    }

    public static function markCurrentSessionLoggedOut(Model $authenticatable, string $guard, Request $request): void
    {
        $query = self::query()
            ->whereMorphedTo('authenticatable', $authenticatable)
            ->where('guard', $guard)
            ->where('successful', true)
            ->whereNull('logout_at');

        self::applyCurrentContext($query, $authenticatable, $request)
            ->latest('login_at')
            ->limit(1)
            ->update([
                'logout_at' => now(),
                'last_seen_at' => now(),
                'last_activity_url' => $request->fullUrl(),
            ]);
    }

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'successful' => 'boolean',
        ];
    }

    private static function sessionId(Request $request): ?string
    {
        if ($request->hasSession()) {
            return $request->session()->getId();
        }

        return session()->isStarted() ? session()->getId() : null;
    }

    private static function accessTokenId(?Model $authenticatable): ?int
    {
        if (! $authenticatable || ! method_exists($authenticatable, 'currentAccessToken')) {
            return null;
        }

        return $authenticatable->currentAccessToken()?->id;
    }

    private static function applyCurrentContext($query, Model $authenticatable, Request $request)
    {
        $accessTokenId = self::accessTokenId($authenticatable);

        if ($accessTokenId) {
            return $query->where('access_token_id', $accessTokenId);
        }

        return $query->where('session_id', self::sessionId($request));
    }
}
