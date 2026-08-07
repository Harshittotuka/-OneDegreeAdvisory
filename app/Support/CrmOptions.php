<?php

namespace App\Support;

class CrmOptions
{
    public const STATUSES = [
        'new' => 'New lead', 'not_answered' => 'Not answered', 'call_back' => 'Call back',
        'follow_up' => 'Follow up', 'interested' => 'Interested', 'future_lead' => 'Future lead',
        'not_interested' => 'Not interested',
        'converted' => 'Enrolled student', 'junk' => 'Junk / invalid', 'dropped' => 'Dropped',
    ];

    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    /** Statuses counsellors may set directly before a lead enters the student journey. */
    public static function pipelineStatuses(): array
    {
        return array_filter(self::STATUSES, fn (string $key): bool => $key !== 'converted', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Statuses that mean the conversation is still open, so the lead belongs in the
     * Follow-up planner whether or not a follow-up date has been scheduled yet.
     * These are the values tinted brown wherever the pipeline status is picked.
     */
    public const FOLLOW_UP_STATUSES = ['not_answered', 'call_back', 'follow_up', 'interested', 'future_lead'];

    /**
     * Pseudo-value for the status filter meaning "any of the above", so the
     * dashboard's follow-up card can open a list holding exactly what it counts.
     * Never stored on a lead — it only ever travels as ?status=.
     */
    public const FOLLOW_UP_GROUP = 'any_follow_up';

    /**
     * Counselling and shortlisting are recorded by hand on the Pipeline control
     * card — separately, because a lead is often counselled long before a
     * shortlist exists — and share this answer set. Leaving either unset is a
     * valid answer — "nobody has checked yet" — so the dropdowns keep a blank
     * option and both columns stay nullable.
     */
    public const DONE_STATES = ['yes' => 'Yes', 'no' => 'No'];

    /** The two lead columns DONE_STATES answers, in the order they are shown. */
    public const DONE_FIELDS = ['counselling' => 'Counselling', 'shortlisting' => 'Shortlisting'];

    /**
     * Pseudo-value for the counselling / shortlisting filters meaning "nobody has
     * recorded this yet". Never stored on a lead — it only ever travels as a
     * query string, where it selects the rows holding null.
     */
    public const NOT_RECORDED = 'not_recorded';

    public const CATEGORIES = [
        'undergraduate' => 'Undergraduate', 'postgraduate' => 'Postgraduate', 'mbbs' => 'MBBS',
        'test_prep' => 'Test preparation', 'career_counselling' => 'Career counselling',
        'visa' => 'Visa', 'other' => 'Other',
    ];

    public const LEAD_ORIGINS = [
        'website' => 'Website', 'manual' => 'Manually added', 'import' => 'CSV / Excel import',
        'enrollment' => 'Enrollment checkout',
    ];

    public const LEAD_TYPES = [
        'general' => 'General enquiry',
        'student_profiler' => 'Student profiler',
        'loan_accommodation' => 'Loan & accommodation',
        'statement_of_purpose' => 'Statement of purpose',
        'visa_mock_interview' => 'Visa mock interview',
        'career_library' => 'Career library',
        'career_counselling' => 'Career counselling',
        'referral' => 'Referral program',
        'enrollment' => 'Enrollment',
    ];

    /**
     * The student journey, in order. "Alumni" is the one stage that sits after
     * the process rather than inside it: it is set only once everything is done
     * and the student is placed, which is what makes it worth filtering on.
     */
    public const STUDENT_STAGES = [
        'doc_pending' => 'Documentation pending', 'doc_complete' => 'Documents complete',
        'app_submitted' => 'Application submitted', 'offer_received' => 'Offer letter received',
        'deposit_paid' => 'Deposit / tuition paid', 'visa_in_process' => 'Visa in process',
        'visa_filed' => 'Visa filed', 'visa_granted' => 'Visa granted',
        'alumni' => 'Alumni', 'visa_rejected' => 'Visa rejected', 'dropped' => 'Student dropped',
    ];

    public const STUDENT_CATEGORIES = [
        'paid' => 'Paid student', 'non_paid' => 'Non-paid student', 'enrollment_fee_paid' => 'Enrollment fee paid',
    ];

    /**
     * Student types that mean money has changed hands. Converting into one of
     * these has to carry a real amount and the receipt it came from — a "paid
     * student" recorded at ₹0 with no reference is the data gap that made a
     * mistaken conversion impossible to tell from a real one.
     */
    public const PAID_STUDENT_CATEGORIES = ['paid', 'enrollment_fee_paid'];

    /** English proficiency tests offered on the lead's academic background card. */
    public const ENGLISH_TESTS = [
        'not_taken' => 'Not taken',
        'ielts' => 'IELTS',
        'toefl' => 'TOEFL',
        'pte' => 'PTE Academic',
        'duolingo' => 'Duolingo English Test',
        'cambridge' => 'Cambridge English (C1 / C2)',
        'other' => 'Other — enter test name',
    ];

    /** Aptitude / standardised tests offered on the lead's academic background card. */
    public const APTITUDE_TESTS = [
        'not_taken' => 'Not taken',
        'sat' => 'SAT',
        'act' => 'ACT',
        'gre' => 'GRE',
        'gmat' => 'GMAT',
        'lsat' => 'LSAT',
        'mcat' => 'MCAT',
        'ucat' => 'UCAT',
        'nmat' => 'NMAT',
        'other' => 'Other — enter test name',
    ];

    /**
     * Render a lead's saved test rows as one readable line, e.g.
     * "IELTS 7.5 (14 Mar 2026) · TOEFL 105".
     *
     * @param  mixed  $rows  the stored array of ['test','name','score','date']
     * @param  array<string, string>  $catalog  ENGLISH_TESTS or APTITUDE_TESTS
     */
    public static function describeTests(mixed $rows, array $catalog): string
    {
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }

        return collect(is_array($rows) ? $rows : [])
            ->map(function ($row) use ($catalog): string {
                if (! is_array($row)) {
                    return '';
                }
                $key = (string) ($row['test'] ?? '');
                $name = trim((string) ($row['name'] ?? ''));
                $label = $key === 'other' || $key === ''
                    ? ($name ?: 'Test')
                    : ($catalog[$key] ?? $key);
                $score = trim((string) ($row['score'] ?? ''));
                $date = trim((string) ($row['date'] ?? ''));
                $suffix = $date === '' ? '' : ' ('.\Illuminate\Support\Carbon::parse($date)->format('d M Y').')';

                return trim($label.($score === '' ? '' : ' '.$score).$suffix);
            })
            ->filter()
            ->implode(' · ');
    }
}
