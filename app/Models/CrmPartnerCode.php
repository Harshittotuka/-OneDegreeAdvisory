<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * A referral company and the tracking code we gave them.
 *
 * The code travels in a public link — /profiler?partner=CODE. When a form is
 * submitted from such a link the lead is attributed to this record and this
 * company's email is notified alongside our own mailbox.
 *
 * Usually the other half of a Partner ACCOUNT: a partner created from the Team
 * screen gets both at once, and crm_user_id names the login that belongs to this
 * company. It stays null for a company we only track — a code issued before the
 * two were joined, or a referrer who never asked for a workspace — so every
 * method here has to work without an account behind it.
 */
class CrmPartnerCode extends Model
{
    protected $fillable = [
        'crm_user_id', 'company_name', 'code', 'email', 'phone', 'contact_name', 'company_link', 'is_active', 'created_by',
    ];

    /**
     * What a change to this record means, for the audit log: the fields worth
     * recording a before and after for. Both screens that edit a partner record
     * the same set, so "what changed" reads the same whichever was used.
     */
    public const TRACKED_FIELDS = ['company_name', 'code', 'email', 'phone', 'contact_name', 'company_link'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * The one shape a code is ever stored or compared in: upper case, no inner
     * whitespace. Codes are handed out by hand and typed into links by other
     * people, so "acme10", "ACME 10" and "Acme10" have to be the same code.
     *
     * Callers normalise the input BEFORE validating it, not after, so the
     * character rule and the uniqueness check both run against that one shape.
     * Checking the raw value instead let "acme10" pass as unique against a
     * stored "ACME10" and then collide on the column's own unique index.
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

    /** The partner account that signs in for this company, where there is one. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'crm_user_id');
    }

    /**
     * The contact details a linked code takes from its account.
     *
     * One company, one set of details: a partner's email is the address their
     * OTP goes to AND the address their referrals are emailed to, so it is held
     * in one place — the account — and mirrored here whenever either side is
     * saved. Without this the two drifted apart and the referral notice went to
     * an address nobody had updated.
     *
     * @return array{email: string, phone: ?string, contact_name: ?string}
     */
    public static function contactFrom(CrmUser $account): array
    {
        return [
            'email' => mb_strtolower(trim((string) $account->email)),
            'phone' => trim((string) $account->phone) ?: null,
            'contact_name' => trim((string) $account->name) ?: null,
        ];
    }

    /**
     * The company half of a partner — the rules both edit paths share.
     *
     * Partner accounts are created on the Team screen and codes are edited on
     * the Partner codes tab, so these rules are stated once here rather than
     * copied into two controllers that would drift apart. $required is false
     * only where the record may legitimately have no company yet: a partner
     * account issued before codes and accounts were joined.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function companyRules(?self $existing = null, bool $required = true): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'company_name' => [$presence, 'string', 'max:150'],
            // The code goes into a URL and is typed by hand, so it is held to
            // link-safe characters and compared upper-cased (see normaliseCode)
            // — "acme10" and "ACME10" are one code.
            'code' => [
                $presence, 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                Rule::unique('crm_partner_codes', 'code')->ignore($existing?->id),
            ],
            'company_link' => ['nullable', 'url', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public static function companyMessages(): array
    {
        return [
            'company_name.required' => 'Enter the referral company this partner represents.',
            'code.required' => 'Enter the tracking code the link for this partner will carry.',
            'code.regex' => 'A code can use letters, numbers, hyphens and underscores only — it travels in a link.',
            'code.unique' => 'Another partner already has this code.',
            'company_link.url' => 'Enter the full company website, including https://.',
        ];
    }

    /**
     * Validated company input in the one shape it is stored in.
     *
     * @param  array<string, mixed>  $data
     * @return array{company_name: string, code: string, company_link: ?string}
     */
    public static function companyValues(array $data): array
    {
        return [
            'company_name' => trim((string) ($data['company_name'] ?? '')),
            'code' => self::normaliseCode($data['code'] ?? null),
            'company_link' => trim((string) ($data['company_link'] ?? '')) ?: null,
        ];
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

    /**
     * This record as an audit-log subject.
     *
     * Stated once because a code is now written from two screens, and an entry
     * that named the subject differently depending on which one was used would
     * be that much harder to follow.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public function auditSubject(array $changes = []): array
    {
        return [
            'subject_type' => 'partner_code',
            'subject_id' => $this->id,
            'subject_label' => $this->label(),
            'changes' => $changes,
        ];
    }

    /** "Acme Education (ACME10)" — how the code reads on a lead or in an email. */
    public function label(): string
    {
        return $this->company_name.' ('.$this->code.')';
    }
}
