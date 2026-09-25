<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_number', 'name', 'phone', 'email', 'city', 'course_interest', 'country_interest',
        'tenth_score', 'tenth_passing_year', 'twelfth_score', 'twelfth_passing_year',
        'graduation_score', 'graduation_passing_year', 'backlogs', 'intake',
        'counselling', 'shortlisting', 'english_tests', 'aptitude_tests',
        'category', 'priority', 'source', 'lead_origin', 'lead_type', 'status', 'assigned_to', 'partner_id', 'partner_code_id', 'created_by', 'follow_up_at',
        'follow_up_completed_at', 'last_contacted_at', 'tags', 'profile', 'is_student',
        'student_stage', 'student_category', 'enrollment_amount', 'enrollment_date',
        'payment_reference', 'conversion_remarks',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime', 'follow_up_completed_at' => 'datetime',
            'last_contacted_at' => 'datetime', 'tags' => 'array', 'profile' => 'array',
            'is_student' => 'boolean', 'enrollment_date' => 'date', 'enrollment_amount' => 'integer',
            'english_tests' => 'array', 'aptitude_tests' => 'array', 'tenth_passing_year' => 'integer',
            'twelfth_passing_year' => 'integer', 'graduation_passing_year' => 'integer',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    /**
     * The referral partner this lead belongs to — the "Partner name" field. Set
     * by a counsellor or a super admin; never by the partner themselves.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'partner_id');
    }

    /**
     * The referral company whose code was on the URL when this lead first came
     * in — the "Partner code" field. Set automatically on capture and only ever
     * corrected by the team; it never controls who can see the lead.
     */
    public function partnerCode(): BelongsTo
    {
        return $this->belongsTo(CrmPartnerCode::class, 'partner_code_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmLeadActivity::class)->latest();
    }

    /** Most recent timeline entry — used for the "Remarks" column on the leads table. */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(CrmLeadActivity::class)->latestOfMany();
    }

    /** The student journey planner, once one has been opened for this enrolled student. */
    public function journeyPlan(): HasOne
    {
        return $this->hasOne(CrmJourneyPlan::class, 'crm_lead_id');
    }

    /** The student's own sign-in to that planner, created when the counsellor starts it. */
    public function studentAccount(): HasOne
    {
        return $this->hasOne(CrmStudentAccount::class, 'crm_lead_id');
    }

    public function websiteSubmissions(): HasMany
    {
        return $this->hasMany(CrmWebsiteSubmission::class)->latest('submitted_at');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'crm_lead_id');
    }

    /**
     * A super admin sees the whole workspace, a counsellor the leads assigned to
     * them, and a partner the leads naming them in the Partner field. Every list,
     * count, export and single-record lookup goes through here, so a partner's
     * reach is decided in one place rather than per screen.
     */
    public function scopeVisibleTo(Builder $query, CrmUser $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $user->isPartner()
            ? $query->where('partner_id', $user->id)
            : $query->where('assigned_to', $user->id);
    }

    /**
     * Everything the Follow-up planner holds: a lead sitting on an open status,
     * or one with a scheduled follow-up still to be completed.
     *
     * Defined once and shared by the planner's list and its counts. Spelling it
     * out in both places is what let the sidebar badge drift out of step with
     * the list it opens.
     */
    public function scopeOpenConversation(Builder $query): Builder
    {
        return $query->where(fn (Builder $open) => $open
            ->whereIn('status', \App\Support\CrmOptions::FOLLOW_UP_STATUSES)
            ->orWhere(fn (Builder $scheduled) => $scheduled
                ->whereNotNull('follow_up_at')->whereNull('follow_up_completed_at')));
    }
}
