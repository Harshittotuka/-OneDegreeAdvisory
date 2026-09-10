<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A referral company and the tracking code we gave them.
 *
 * The code travels in a public link — /profiler?partner=CODE. When a form is
 * submitted from such a link the lead is attributed to this record and this
 * company's email is notified alongside our own mailbox.
 *
 * Not to be confused with the Partner role on CrmUser, which is a CRM login for
 * a partner who watches their students inside the workspace.
 */
class CrmPartnerCode extends Model
{
    protected $fillable = [
        'company_name', 'code', 'email', 'phone', 'contact_name', 'company_link', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * The one shape a code is ever stored or compared in: upper case, no inner
     * whitespace. Codes are handed out by hand and typed into links by other
     * people, so "acme10", "ACME 10" and "Acme10" have to be the same code.
     */
    public static function normaliseCode(?string $code): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/', '', (string) $code)));
    }

    /**
     * The active partner behind a ?partner= value, or null.
     *
     * A code that was never issued, was mistyped, or belongs to a switched-off
     * company resolves to nothing: the submission is still captured and our own
     * mailbox is still notified, it simply carries no attribution.
     */
    public static function resolve(?string $code): ?self
    {
        $code = self::normaliseCode($code);

        return $code === '' ? null : self::query()->where('code', $code)->where('is_active', true)->first();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'partner_code_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The link a partner shares with students. */
    public function profilerUrl(): string
    {
        return route('profiler', ['partner' => $this->code]);
    }

    /** "Acme Education (ACME10)" — how the code reads on a lead or in an email. */
    public function label(): string
    {
        return $this->company_name.' ('.$this->code.')';
    }
}
