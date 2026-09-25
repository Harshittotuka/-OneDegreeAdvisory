<?php

namespace App\Support;

/**
 * ODA's Overseas Admission Journey Planner — the reference content and the
 * progress rules behind the student journey planner.
 *
 * Everything here comes from the planner workbook ODA used before this screen
 * existed ("ODA Overseas Admission Journey Planner.xlsx"): the seven one-time
 * Core Journey phases, the 22 activities repeated for every university, the
 * status and owner lists, and the Overview / Applications Summary formulas.
 *
 * Activity keys are stored in each plan's JSON, so a key must never be renamed
 * or reused. Adding an activity is safe: normalise() fills it in for plans that
 * were created before it existed.
 */
class JourneyPlanner
{
    public const STATUSES = ['Not Started', 'In Progress', 'Submitted', 'Completed', 'Not Applicable'];

    public const OWNERS = [
        'Student', 'Counsellor', 'University', 'Bank/Financial Institution', 'Student & Counsellor',
    ];

    public const FITS = ['reach' => 'Reach', 'match' => 'Match', 'safe' => 'Safe'];

    public const OFFER_TYPES = ['Unconditional', 'Conditional', 'Waitlist', 'Rejected'];

    public const LEVELS = ['Undergraduate', 'Masters', 'MBA', 'PhD', 'Diploma', 'MBBS'];

    /** Statuses a student may pick for themselves. "Not Applicable" is the counsellor's call. */
    public const STUDENT_STATUSES = ['Not Started', 'In Progress', 'Submitted', 'Completed'];

    /**
     * The Core Journey tab: done once per student, however many universities
     * they apply to. [key, activity, description, owner, document checklist, included by default]
     *
     * @return list<array{key: string, name: string, short: string, timeline: string, activities: list<array{key: string, name: string, desc: string, owner: string, docs: string, inc: bool}>}>
     */
    public static function phases(): array
    {
        $phases = [
            ['discovery', 'Discovery & Profile Assessment', 'Discovery', '18–24 months before intake', [
                ['initial-consultation', 'Initial counselling consultation', 'Discuss goals, budget and expectations with the assigned counsellor.', 'Student', 'None', true],
                ['academic-review', 'Academic record review', 'Collect and review mark sheets/transcripts from Class 9 onward.', 'Student', 'School mark sheets, transcripts', true],
                ['aptitude-assessment', 'Aptitude / interest assessment', 'Complete a psychometric or interest-based assessment to identify direction.', 'Student', 'Assessment report', true],
                ['budget-conversation', 'Budget & funding conversation', 'Confirm a comfortable budget range for the full program.', 'Student', 'None', true],
                ['intake-finalisation', 'Target intake finalisation', 'Agree on a realistic target intake session (e.g., Fall 2028).', 'Student & Counsellor', 'None', true],
                ['gap-analysis', 'Gap analysis', 'Identify gaps in grades, tests, extracurriculars or documents to address early.', 'Counsellor', 'None', true],
            ]],
            ['shortlisting', 'Country & Course Shortlisting', 'Shortlisting', '16–20 months before intake', [
                ['country-research', 'Country research', 'Compare cost of living, post-study work rights and safety across candidate countries.', 'Student & Counsellor', 'None', true],
                ['course-research', 'Course direction research', "Explore course options aligned to the student's interests and career goals.", 'Student & Counsellor', 'None', true],
                ['preliminary-shortlist', 'Preliminary shortlisting', 'Draft a working list of countries and course directions.', 'Counsellor', 'None', true],
                ['family-sign-off', 'Shortlist review & sign-off', 'Review the findings with the counsellor and confirm the direction before proceeding.', 'Student & Counsellor', 'None', true],
            ]],
            ['profile', 'Profile Building / Skill Enhancers', 'Profile building', 'Ongoing, ideally 18–6 months before intake', [
                ['research-project', 'Research project', 'Undertake a subject-relevant research project or paper.', 'Student', 'Project report/certificate', false],
                ['internship', 'Internship', 'Secure a relevant internship or work-shadowing placement.', 'Student', 'Internship certificate/letter', false],
                ['passion-project', 'Passion project', 'Develop an independent project that shows genuine initiative.', 'Student', 'Project documentation/portfolio', false],
                ['entrepreneurship', 'Entrepreneurship initiative', 'Start or meaningfully contribute to a small venture or initiative.', 'Student', 'Supporting evidence (website, sales, media)', false],
                ['community-work', 'Community work', 'Complete meaningful, sustained community service or volunteering.', 'Student', 'Volunteering certificate/letter', false],
                ['competitions', 'Competitions', 'Participate in relevant academic or extracurricular competitions.', 'Student', 'Participation/award certificate', false],
                ['scholarship-research', 'Scholarship research', 'Identify and shortlist scholarships the student may be eligible for.', 'Counsellor', 'Scholarship shortlist', true],
            ]],
            ['tests', 'Standardised Tests & Language Proficiency', 'Tests', '12–16 months before intake', [
                ['english-test', 'English proficiency test', 'Register for and take IELTS, TOEFL or PTE as required.', 'Student', 'Test registration confirmation, score report', true],
                ['aptitude-test', 'Aptitude test', 'Register for and take SAT/ACT (undergraduate) or GRE/GMAT (graduate) as applicable.', 'Student', 'Score report', true],
                ['subject-test', 'Subject / country-specific test', 'Register for and take any additional test a program requires.', 'Student', 'Score report', false],
                ['retake-planning', 'Retake planning (if needed)', "Book a retake within the score validity window, if targets aren't met.", 'Student', 'Updated score report', false],
            ]],
            ['finance', 'Financial Planning & Documentation', 'Finance', '6–9 months before intake', [
                ['funding-finalisation', 'Funding source finalisation', 'Confirm the final mix of own funds, scholarship and/or loan.', 'Student', 'None', true],
                ['education-loan', 'Education loan application', 'Apply for and compare loan offers on rate, moratorium and collateral.', 'Student', 'Loan application, sanction letter', false],
                ['scholarship-application', 'Scholarship application', 'Apply to every scholarship the student is eligible for.', 'Student', 'Scholarship application, award letter', false],
                ['proof-of-funds', 'Proof-of-funds preparation', 'Assemble the visa-ready financial document set.', 'Student', 'Bank statements, FDs, sanction letter', true],
            ]],
            ['accommodation', 'Accommodation Planning', 'Accommodation', '3–5 months before intake, for the confirmed university', [
                ['accommodation-research', 'Accommodation research', 'Compare university halls of residence against private rental options.', 'Student', 'None', true],
                ['accommodation-booking', 'Application / booking', 'Apply for or book the confirmed accommodation.', 'Student', 'Booking confirmation/tenancy agreement', true],
                ['accommodation-deposit', 'Deposit / first payment', 'Pay the accommodation deposit or first payment.', 'Student', 'Payment receipt', true],
                ['tenancy-review', 'Documentation review', 'Review lease/tenancy terms fully before signing.', 'Student', 'Signed tenancy agreement', true],
            ]],
            ['departure', 'Pre-Departure Handoff', 'Pre-departure', '1–3 months before departure', [
                ['pre-departure-session', 'Attend ODA Pre-Departure Session', "Attend ODA's Pre-Departure briefing — visa, travel, money, academics, safety, career.", 'Student', 'See Pre-Departure Session checklist', true],
                ['flight-booking', 'Flight booking', 'Book flights aligned with university reporting/orientation dates.', 'Student', 'Flight booking confirmation', true],
                ['final-document-check', 'Final document check', 'Complete the full pre-departure document checklist before travel.', 'Student', 'See Pre-Departure Session checklist', true],
            ]],
        ];

        return array_map(fn (array $p): array => [
            'key' => $p[0], 'name' => $p[1], 'short' => $p[2], 'timeline' => $p[3],
            'activities' => array_map(self::activityDef(...), $p[4]),
        ], $phases);
    }

    /**
     * The University Applications tab: the same 22 activities for every
     * university or programme. The workbook lists them flat; the groups only
     * make a long checklist easier to scan.
     *
     * @return list<array{name: string, activities: list<array{key: string, name: string, desc: string, owner: string, docs: string, inc: bool}>}>
     */
    public static function applicationGroups(): array
    {
        $groups = [
            ['Fit & requirements', [
                ['fit', 'Confirm program fit (Reach / Match / Safe)', 'Classify this specific program as reach, match or safe for the student.', 'Counsellor', 'None', true],
                ['entry-requirements', 'Verify entry requirements', 'Confirm grades, tests and prerequisites are met for this program.', 'Counsellor', 'Program entry-requirement sheet', true],
                ['deadline', 'Note deadline & required tests', "Record this program's specific deadline and required test(s).", 'Counsellor', 'Deadline tracker', true],
                ['english-score', 'Confirm English proficiency score meets requirement', "Check the IELTS/TOEFL/PTE score against this program's minimum.", 'Student & Counsellor', 'Score report', true],
                ['aptitude-score', 'Confirm aptitude test score meets requirement', "Check SAT/ACT/GRE/GMAT score against this program's minimum, where required.", 'Student & Counsellor', 'Score report', true],
                ['subject-score', 'Confirm subject/country-specific test requirement', 'Confirm any additional required test is complete and meets the threshold.', 'Student & Counsellor', 'Score report', false],
            ]],
            ['Documents', [
                ['sop', 'SOP tailored to this program', "Adapt the master SOP to this program's specific prompt and focus.", 'Student', 'Program-specific SOP', true],
                ['lors', 'LORs addressed to this program', 'Confirm recommendation letters are addressed/submitted for this program.', 'Student', 'Signed LORs', true],
                ['cv', 'Resume/CV tailored (if required)', "Adjust CV emphasis if this program's application asks for it.", 'Student', 'Program-specific CV', false],
                ['transcripts', 'Transcripts sent/uploaded', 'Send or upload attested transcripts to this program.', 'Student', 'Transcripts', true],
                ['financial-documents', 'Financial documents uploaded', "Upload proof-of-funds documents if this program's application asks for them.", 'Student', 'Bank statements/sponsor letter', true],
            ]],
            ['Application', [
                ['portal-account', 'Application portal account created', "Register on this program's application portal.", 'Student', 'Login credentials record', true],
                ['application-form', 'Application form completed', 'Complete the form and any supplementary essays.', 'Student', 'Completed application form', true],
                ['application-fee', 'Application fee paid', 'Pay the application fee, or apply for a waiver.', 'Student', 'Payment receipt', true],
                ['submitted', 'Application submitted & confirmed', 'Submit and confirm receipt of the complete application.', 'Student', 'Submission confirmation', true],
            ]],
            ['Interview & portfolio', [
                ['interview-scheduled', 'Interview scheduled', 'Confirm date/time if this program requires an interview.', 'Counsellor', 'Interview confirmation email', false],
                ['interview-completed', 'Interview completed', 'Attend the admissions interview.', 'Student', 'None', false],
                ['portfolio', 'Portfolio / audition submitted', 'Submit or perform a required portfolio/audition.', 'Student', 'Portfolio/audition materials', false],
            ]],
            ['Offer & visa', [
                ['offer', 'Offer received', 'Log the offer type (unconditional / conditional / waitlist).', 'Counsellor', 'Offer letter', true],
                ['seat-confirmed', 'Seat confirmed & deposit paid', 'Accept the offer and pay the confirmation deposit, if this is the chosen university.', 'Student', 'Payment receipt', true],
                ['visa-submitted', 'Visa application submitted', 'Submit the visa application — complete only for the confirmed university.', 'Student', 'Visa application receipt', false],
                ['visa-approved', 'Visa approved', 'Track and record the visa outcome for the confirmed university.', 'Student', 'Visa approval', false],
            ]],
        ];

        return array_map(fn (array $g): array => [
            'name' => $g[0],
            'activities' => array_map(self::activityDef(...), $g[1]),
        ], $groups);
    }

    /**
     * The seven phases with this plan's own tasks added at the end of the
     * phase each belongs to. Standard tasks carry `custom: false`.
     */
    public static function phasesFor(\App\Models\CrmJourneyPlan $plan, bool $hideEmptyAdded = false): array
    {
        $custom = $plan->customDefinitions();

        $phases = array_map(function (array $p) use ($custom): array {
            $p['activities'] = array_map(fn (array $a) => $a + ['custom' => false], $p['activities']);
            foreach ($custom as $def) {
                if ($def['phase'] === $p['key']) {
                    $p['activities'][] = $def;
                }
            }

            return $p;
        }, $plan->orderedPhases());

        // A stage the counsellor added but hasn't filled yet means nothing to a student.
        return array_values($hideEmptyAdded ? array_filter($phases, fn ($p) => ! $p['custom'] || $p['activities'] !== []) : $phases);
    }

    /** @return array<string, array{key: string, name: string, desc: string, owner: string, docs: string, inc: bool}> */
    public static function coreDefinitions(): array
    {
        return collect(self::phases())->flatMap(fn (array $p) => $p['activities'])->keyBy('key')->all();
    }

    /** @return array<string, array{key: string, name: string, desc: string, owner: string, docs: string, inc: bool}> */
    public static function applicationDefinitions(): array
    {
        return collect(self::applicationGroups())->flatMap(fn (array $g) => $g['activities'])->keyBy('key')->all();
    }

    /** A fresh Core Journey: every activity at its default Include, nothing started. */
    public static function freshCore(): array
    {
        return self::normalise([], self::coreDefinitions());
    }

    public static function freshApplication(): array
    {
        return self::normalise([], self::applicationDefinitions());
    }

    /**
     * Stored activity state, completed against the template: activities added
     * to the template since the plan was created appear with their defaults,
     * and anything the template no longer knows is dropped.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, array{owner: string, inc: bool}>  $definitions
     * @return array<string, array{inc: bool, status: string, owner: string, target: ?string, done: ?string, notes: string, by: ?string, at: ?string}>
     */
    public static function normalise(array $stored, array $definitions): array
    {
        $out = [];
        foreach ($definitions as $key => $def) {
            $row = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            $out[$key] = [
                'inc' => array_key_exists('inc', $row) ? (bool) $row['inc'] : $def['inc'],
                'status' => in_array($row['status'] ?? null, self::STATUSES, true) ? $row['status'] : 'Not Started',
                'owner' => self::owner($row['owner'] ?? null, $def['owner']),
                'target' => self::dateOrNull($row['target'] ?? null),
                'done' => self::dateOrNull($row['done'] ?? null),
                'notes' => is_string($row['notes'] ?? null) ? $row['notes'] : '',
                'by' => is_string($row['by'] ?? null) ? $row['by'] : null,
                'at' => is_string($row['at'] ?? null) ? $row['at'] : null,
            ];
        }

        return $out;
    }

    /**
     * The workbook's rollup: only included activities count, and
     * % complete = Completed ÷ (Included − Not Applicable).
     *
     * @param  iterable<array{inc: bool, status: string}>  $activities
     * @return array{included: int, completed: int, in_progress: int, not_started: int, not_applicable: int, percent: int}
     */
    public static function rollup(iterable $activities): array
    {
        $r = ['included' => 0, 'completed' => 0, 'in_progress' => 0, 'not_started' => 0, 'not_applicable' => 0];
        foreach ($activities as $a) {
            if (! $a['inc']) {
                continue;
            }
            $r['included']++;
            match ($a['status']) {
                'Completed' => $r['completed']++,
                'In Progress', 'Submitted' => $r['in_progress']++,
                'Not Applicable' => $r['not_applicable']++,
                default => $r['not_started']++,
            };
        }
        $denominator = $r['included'] - $r['not_applicable'];
        $r['percent'] = $denominator > 0 ? (int) round($r['completed'] / $denominator * 100) : 0;

        return $r;
    }

    /**
     * Apply validated field changes to one activity's state. Marking an
     * activity Completed without a completion date records today, as the
     * workbook's "Completion Date" column would have been filled by hand.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    public static function apply(array $row, array $changes, string $by): array
    {
        foreach (['inc', 'status', 'owner', 'target', 'done', 'notes'] as $field) {
            if (array_key_exists($field, $changes)) {
                $row[$field] = match ($field) {
                    'inc' => (bool) $changes[$field],
                    'notes' => trim((string) $changes[$field]),
                    'target', 'done' => $changes[$field] ?: null,
                    default => $changes[$field],
                };
            }
        }
        if (($changes['status'] ?? null) === 'Completed' && empty($row['done'])) {
            $row['done'] = now()->toDateString();
        }
        $row['by'] = $by;
        $row['at'] = now()->toIso8601String();

        return $row;
    }

    /**
     * Everything the planner page needs, as one JSON document: the reference
     * content, this student's state, and who is looking.
     *
     * @param  'counsellor'|'partner'|'student'  $mode
     * @param  array<string, string|null>  $endpoints
     * @return array<string, mixed>
     */
    public static function payload(\App\Models\CrmJourneyPlan $plan, string $mode, array $endpoints = [], ?array $credentials = null): array
    {
        $lead = $plan->lead;
        $account = $lead->studentAccount;

        return [
            'mode' => $mode,
            'today' => now()->toDateString(),
            'student' => [
                'name' => $lead->name,
                'firstName' => \Illuminate\Support\Str::before(trim($lead->name).' ', ' '),
                'counsellor' => $lead->assignee?->name,
                'level' => (string) $plan->level,
                'intake' => (string) $plan->intake,
                'focus' => (string) $plan->focus,
                'leadNumber' => $mode === 'student' ? null : $lead->lead_number,
            ],
            'template' => [
                'phases' => self::phasesFor($plan, $mode !== 'counsellor'),
                'appGroups' => self::applicationGroups(),
                'statuses' => self::STATUSES,
                'studentStatuses' => self::STUDENT_STATUSES,
                'owners' => self::OWNERS,
                'fits' => self::FITS,
                'offerTypes' => self::OFFER_TYPES,
                'levels' => self::LEVELS,
            ],
            'core' => $plan->coreState(),
            'apps' => $plan->applications->map->toPlannerArray()->values()->all(),
            // The student's sign-in, for the counsellor's "Student login" card.
            // The password itself is only ever the one just issued, shown once.
            'login' => $mode === 'counsellor' && $account ? [
                'email' => $account->email,
                'active' => $account->is_active,
                'mustChange' => $account->must_change_password,
                'lastLoginAt' => $account->last_login_at?->toIso8601String(),
                'loginUrl' => route('student.login'),
                'adminPassword' => $account->adminPassword(),
            ] : null,
            'credentials' => $mode === 'counsellor' ? $credentials : null,
            'account' => $mode === 'student' && $account ? ['email' => $account->email] : null,
            'documents' => $plan->documents()->with(['application', 'creator', 'reviewer'])->get()
                ->map(fn ($d) => JourneyDocuments::toArray($d, $mode, fn ($doc) => $mode === 'student'
                    ? route('student.documents.file', $doc)
                    : route('crm.journey.documents.file', [$lead, $doc])))->values()->all(),
            'docTemplate' => [
                'fileCategories' => JourneyDocuments::FILE_CATEGORIES,
                'essayCategories' => JourneyDocuments::ESSAY_CATEGORIES,
                'essayStatuses' => JourneyDocuments::ESSAY_STATUSES,
                'limits' => JourneyDocuments::limits(),
            ],
            'csrf' => csrf_token(),
            'endpoints' => $endpoints,
        ];
    }

    /** Whether the student may change this activity from their portal: the owner has to name the Student. */
    public static function studentOwns(string $owner): bool
    {
        return str_contains($owner, 'Student');
    }

    private static function activityDef(array $a): array
    {
        return ['key' => $a[0], 'name' => $a[1], 'desc' => $a[2], 'owner' => $a[3], 'docs' => $a[4], 'inc' => $a[5]];
    }

    /**
     * A stored owner, or the template's when it's missing or unknown. Plans
     * saved before the planner dropped the Parent owner are read with the
     * parent's share going to the student.
     */
    private static function owner(mixed $stored, string $default): string
    {
        $legacy = ['Parent' => 'Student', 'Student & Parent' => 'Student', 'Student & Parent & Counsellor' => 'Student & Counsellor'];
        $owner = is_string($stored) ? ($legacy[$stored] ?? $stored) : null;

        return in_array($owner, self::OWNERS, true) ? $owner : $default;
    }

    private static function dateOrNull(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
