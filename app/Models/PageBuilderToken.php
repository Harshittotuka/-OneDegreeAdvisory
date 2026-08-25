<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An expiring credential for the Page Builder's machine interface. Created in
 * /admin/pages, pasted into a claude.ai connector, and dead on its expiry date
 * whether or not anyone remembers to revoke it.
 */
class PageBuilderToken extends Model
{
    protected $fillable = [
        'label', 'token_hash', 'hint', 'expires_at', 'last_used_at',
        'revoked_at', 'use_count', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** Not revoked and not expired. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        return $this->expires_at !== null && $this->expires_at->isFuture() ? 'active' : 'expired';
    }

    /** Whole days left, floored at zero. */
    public function daysLeft(): int
    {
        if ($this->expires_at === null || $this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at);
    }
}
