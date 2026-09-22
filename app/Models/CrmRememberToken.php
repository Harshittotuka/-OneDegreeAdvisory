<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * One remembered CRM device.
 *
 * The cookie carries a random secret; only its SHA-256 is stored here, so the
 * table is worth nothing to anyone who reads it. A row per device is what lets
 * a sign-out revoke just the machine it was made on.
 */
class CrmRememberToken extends Model
{
    protected $fillable = ['crm_user_id', 'token_hash', 'expires_at', 'ip_address', 'user_agent'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    /** Mint a token for this device and return the secret the cookie carries. */
    public static function issue(CrmUser $user, Request $request): string
    {
        $secret = bin2hex(random_bytes(32));

        self::query()->create([
            'crm_user_id' => $user->id,
            'token_hash' => self::hash($secret),
            'expires_at' => now()->addDays(self::days()),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ]);

        // Lapsed devices are of no use to anyone; clear them out as we go.
        self::query()->where('expires_at', '<', now())->delete();

        return $secret;
    }

    /** The active account behind a presented cookie, or null if it buys nothing. */
    public static function resolve(?string $secret): ?CrmUser
    {
        $token = self::lookup($secret);
        if (! $token) {
            return null;
        }

        return CrmUser::query()->whereKey($token->crm_user_id)->where('is_active', true)->first();
    }

    /** Retire one device's token, leaving this account's other devices signed in. */
    public static function revoke(?string $secret): void
    {
        self::lookup($secret)?->delete();
    }

    /** Drop every remembered device for an account — used when access is withdrawn. */
    public static function revokeAllFor(CrmUser $user): void
    {
        self::query()->where('crm_user_id', $user->id)->delete();
    }

    /** How long a remembered device stays signed in, in days. */
    public static function days(): int
    {
        return (int) config('crm.remember.days', 30);
    }

    private static function lookup(?string $secret): ?self
    {
        if (! is_string($secret) || $secret === '') {
            return null;
        }

        return self::query()
            ->where('token_hash', self::hash($secret))
            ->where('expires_at', '>', now())
            ->first();
    }

    private static function hash(string $secret): string
    {
        return hash('sha256', $secret);
    }
}
