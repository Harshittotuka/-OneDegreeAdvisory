<?php

namespace App\Http\Controllers;

use App\Mail\ReferralReferrerMail;
use App\Mail\ReferralStudentMail;
use App\Mail\ReferralTeamMail;
use App\Services\WebsiteLeadManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Referral Program — /referral-program (Student Hub).
 *
 * The referral form is the only page on the site that captures two people at
 * once. The CRM records ONE lead per prospective student, so the referred
 * student is the lead (they are the prospect a counsellor will work) and the
 * referrer's details ride along in the submission as their own section, which
 * is what tells the team who to credit when the enrolment is verified.
 */
class ReferralController extends Controller
{
    public function __construct(private WebsiteLeadManager $leads)
    {
    }

    /** Study levels and destinations offered by the referral form's selects. */
    public const LEVELS = ['Bachelor', 'Master', 'MBA', 'PhD / Doctoral', 'Diploma / Foundation'];

    public const COUNTRIES = [
        'USA', 'UK', 'Canada', 'Australia', 'New Zealand', 'Germany', 'Ireland', 'France',
        'Italy', 'Netherlands', 'Singapore', 'UAE', 'Europe', 'Other / not sure yet',
    ];

    public function index(): View
    {
        return view('pages.referral-program', [
            'activeNav' => 'new-tabs',
            'bodyClass' => 'ref-page-body',
            'pageTitle' => 'Referral Program — Refer a Student, Earn Rewards',
            'pageDescription' => 'Refer a student to One Degree Advisory and earn a reward once they enrol. Open to students, alumni, parents, professionals and anyone with someone in their circle planning to study abroad.',
            'levels' => self::LEVELS,
            'countries' => self::COUNTRIES,
        ]);
    }

    /**
     * Record a referral. Posted through the site's shared AJAX handler
     * (wireFormSubmit), which expects {title, message} back to drive the
     * success/fail popup.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer_name' => 'required|string|max:120',
            'referrer_phone' => 'required|string|max:40|regex:/^[0-9+()\-\s]{7,40}$/',
            'referrer_email' => 'required|email:rfc|max:190|real_email',
            'student_name' => 'required|string|max:120',
            'student_phone' => 'required|string|max:40|regex:/^[0-9+()\-\s]{7,40}$/',
            'student_email' => 'required|email:rfc|max:190|real_email',
            'level' => 'required|string|in:'.implode(',', self::LEVELS),
            'country' => 'required|string|in:'.implode(',', self::COUNTRIES),
            'notes' => 'nullable|string|max:1000',
            'consent' => 'accepted',
        ], [
            'consent.accepted' => "Please confirm you have the student's permission to share their details.",
        ]);

        $referrerName = trim($validated['referrer_name']);
        $referrerPhone = trim($validated['referrer_phone']);
        $referrerEmail = trim($validated['referrer_email']);
        $studentName = trim($validated['student_name']);
        $studentPhone = trim($validated['student_phone']);
        $studentEmail = trim($validated['student_email']);
        $notes = trim((string) ($validated['notes'] ?? ''));

        // A referrer nominating themselves is not a referral (and it is excluded
        // by the program terms on the page), so reject it rather than quietly
        // creating a lead that can never be paid out.
        if ($this->sameContact($referrerEmail, $studentEmail) || $this->sameContact($referrerPhone, $studentPhone)) {
            return response()->json([
                'title' => 'Self-referrals are not eligible',
                'message' => 'The referrer and the student need to be two different people, with different contact details. Please check the form and try again.',
            ], 422);
        }

        $studentAnswers = [
            ['label' => 'Name', 'value' => [$studentName]],
            ['label' => 'Email', 'value' => [$studentEmail]],
            ['label' => 'Phone', 'value' => [$studentPhone]],
            // These two labels are load-bearing: WebsiteLeadManager::capture()
            // reads them into the lead's course_interest / country_interest.
            ['label' => 'Study level', 'value' => [$validated['level']]],
            ['label' => 'Preferred country', 'value' => [$validated['country']]],
        ];

        if ($notes !== '') {
            $studentAnswers[] = ['label' => 'Notes from the referrer', 'value' => [Str::limit($notes, 900, '')]];
        }

        $sections = [
            [
                'eyebrow' => 'Referred student',
                'title' => 'Student being referred',
                'answers' => $studentAnswers,
            ],
            [
                'eyebrow' => 'Referred by',
                'title' => 'Referrer — who to credit for this enrolment',
                'answers' => [
                    ['label' => 'Referrer name', 'value' => [$referrerName]],
                    ['label' => 'Referrer email', 'value' => [$referrerEmail]],
                    ['label' => 'Referrer phone', 'value' => [$referrerPhone]],
                    ['label' => 'Consent confirmed', 'value' => ['Yes — referrer confirmed the student agreed to be contacted']],
                ],
            ],
        ];

        // The STUDENT is the lead; the referrer is recorded above. Deliberately
        // not $forceNewLead: if this student is already in the CRM the referral
        // attaches to their existing record, which is exactly what the "must be
        // a new lead" term needs a counsellor to be able to see.
        $this->leads->capture(
            'referral',
            'Referral Program',
            $validated['level'],
            $sections,
            ['name' => $studentName, 'email' => $studentEmail, 'phone' => $studentPhone],
        );

        $this->sendReferralEmails([
            'referrer_name' => $referrerName,
            'referrer_email' => $referrerEmail,
            'referrer_phone' => $referrerPhone,
            'student_name' => $studentName,
            'student_email' => $studentEmail,
            'student_phone' => $studentPhone,
            'level' => $validated['level'],
            'country' => $validated['country'],
            'notes' => $notes,
        ]);

        return response()->json([
            'ok' => true,
            'title' => 'Referral received',
            'message' => 'Thank you, '.Str::limit($referrerName, 40, '').'. We have logged your referral and a counsellor will reach out to '
                .Str::limit($studentName, 40, '').' shortly. You will hear from us as their application progresses.',
        ]);
    }

    /**
     * One referral, three emails: the admissions team, the referrer, and the
     * referred student.
     *
     * Each is sent independently and best-effort. The referral is already saved
     * to the CRM by the time we get here, so a mail failure must never surface as
     * a failed submission — and one recipient bouncing (a typo'd student address
     * is the likely case) must not stop the other two going out.
     *
     * @param array<string,string> $data
     */
    private function sendReferralEmails(array $data): void
    {
        $mailer = config('site.forms.referral.mailer') ?: config('mail.default');
        $team = config('site.forms.referral.to');

        $deliveries = [
            ['to' => $team, 'mail' => new ReferralTeamMail($data), 'label' => 'team'],
            ['to' => $data['referrer_email'], 'mail' => new ReferralReferrerMail($data), 'label' => 'referrer'],
        ];

        // The student did not fill the form in themselves, so this one is
        // switchable per environment (REFERRAL_NOTIFY_STUDENT).
        if (config('site.forms.referral.notify_student')) {
            $deliveries[] = ['to' => $data['student_email'], 'mail' => new ReferralStudentMail($data), 'label' => 'student'];
        }

        foreach ($deliveries as $delivery) {
            if (trim((string) $delivery['to']) === '') {
                continue;
            }

            try {
                Mail::mailer($mailer)->to($delivery['to'])->send($delivery['mail']);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /** Loose equality for contact details — case, spacing and punctuation aside. */
    private function sameContact(string $a, string $b): bool
    {
        $normalise = static function (string $value): string {
            $value = mb_strtolower(trim($value));
            // Compare phone numbers on their last 10 digits, the same way the
            // CRM normalises them, so "+91 98765 43210" and "9876543210" match.
            if (preg_match('/^[0-9+()\-\s]+$/', $value)) {
                $digits = (string) preg_replace('/\D+/', '', $value);

                return $digits === '' ? '' : substr($digits, -10);
            }

            return $value;
        };

        $a = $normalise($a);
        $b = $normalise($b);

        return $a !== '' && $a === $b;
    }
}
