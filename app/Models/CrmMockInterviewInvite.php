<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CrmMockInterviewInvite extends Model
{
    protected $fillable = [
        'token', 'recipient_name', 'recipient_email', 'recipient_phone',
        'question_count', 'max_uses', 'uses_count', 'destination', 'notes',
        'created_by', 'expires_at', 'revoked_at', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
            'question_count' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    public static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (static::query()->where('token', $token)->exists());

        return $token;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CrmMockInterviewAttempt::class, 'invite_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->uses_count >= $this->max_uses;
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isExhausted();
    }

    public function remainingUses(): int
    {
        return max(0, $this->max_uses - $this->uses_count);
    }

    /** Machine-readable reason the link cannot be used, or 'ok'. */
    public function state(): string
    {
        return match (true) {
            $this->isRevoked() => 'revoked',
            $this->isExpired() => 'expired',
            $this->isExhausted() => 'exhausted',
            default => 'ok',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->state()) {
            'revoked' => 'Revoked',
            'expired' => 'Expired',
            'exhausted' => 'All attempts used',
            default => $this->uses_count > 0 ? 'In use' : 'Not opened yet',
        };
    }

    public function shareUrl(): string
    {
        return route('visa-mock.invite', ['token' => $this->token]);
    }
}
