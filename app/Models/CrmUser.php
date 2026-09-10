<?php

namespace App\Models;

use App\Support\CrmOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmUser extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'role', 'partner_access', 'is_active', 'created_by', 'last_login_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'assigned_to');
    }

    /** The leads naming this partner in their Partner field — a partner's whole workspace. */
    public function partnerLeads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'partner_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * An outside referral partner. They see only the leads that name them, and
     * none of the team's own tools — the follow-up planner, the report builder,
     * the mock-interview links, the payment log, the audit trail.
     */
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    /**
     * Whether this account may change a lead it can see.
     *
     * The team always can. A partner only where a super admin granted "edit" —
     * read is the default, so a partner_access that was never set, or holds
     * anything unexpected, means read-only.
     */
    public function canEditLeads(): bool
    {
        return ! $this->isPartner() || $this->partner_access === 'edit';
    }

    public function roleLabel(): string
    {
        return CrmOptions::ROLES[$this->role] ?? ucfirst(str_replace('_', ' ', (string) $this->role));
    }

    /** The read / edit switch as words, for a partner. Empty on any other role. */
    public function partnerAccessLabel(): string
    {
        if (! $this->isPartner()) {
            return '';
        }

        return CrmOptions::PARTNER_ACCESS[$this->partner_access] ?? CrmOptions::PARTNER_ACCESS['read'];
    }
}
