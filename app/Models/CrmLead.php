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
        'counselling_shortlisting', 'english_tests', 'aptitude_tests',
        'category', 'priority', 'source', 'lead_origin', 'lead_type', 'status', 'assigned_to', 'created_by', 'follow_up_at',
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

    public function activities(): HasMany
    {
        return $this->hasMany(CrmLeadActivity::class)->latest();
    }

    /** Most recent timeline entry — used for the "Remarks" column on the leads table. */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(CrmLeadActivity::class)->latestOfMany();
    }

    public function websiteSubmissions(): HasMany
    {
        return $this->hasMany(CrmWebsiteSubmission::class)->latest('submitted_at');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class, 'crm_lead_id');
    }

    public function scopeVisibleTo(Builder $query, CrmUser $user): Builder
    {
        return $user->isSuperAdmin() ? $query : $query->where('assigned_to', $user->id);
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
