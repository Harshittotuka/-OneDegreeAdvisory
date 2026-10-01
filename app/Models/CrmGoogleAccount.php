<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A counsellor's connected Google account. Both tokens are encrypted at rest
 * and never leave the server: the page only ever learns the address and
 * whether the connection still works.
 */
class CrmGoogleAccount extends Model
{
    protected $fillable = [
        'crm_user_id', 'google_email', 'refresh_token', 'access_token', 'access_token_expires_at',
        'scopes', 'connected_at', 'last_refreshed_at', 'needs_reconnect_at', 'last_error', 'pending_rooms',
    ];

    protected $hidden = ['refresh_token', 'access_token'];

    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'access_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
            'needs_reconnect_at' => 'datetime',
            'pending_rooms' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    public function works(): bool
    {
        return $this->needs_reconnect_at === null;
    }

    /** What the planner shows about this connection. */
    public function toStatusArray(): array
    {
        return [
            'connected' => true,
            'email' => (string) $this->google_email,
            'works' => $this->works(),
            'problem' => $this->works() ? null : (string) $this->last_error,
        ];
    }
}
