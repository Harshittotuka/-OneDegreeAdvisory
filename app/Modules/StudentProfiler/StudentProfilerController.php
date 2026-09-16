<?php

namespace App\Modules\StudentProfiler;

use App\Models\CrmPartnerCode;
use App\Support\ProfileReportBuilder;
use App\Support\ProfileReportNotifier;
use App\Services\WebsiteLeadManager;
use App\Support\WebsiteSubmissionData;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Student Profiler — an animated, degree-adaptive profiling questionnaire.
 *
 * Fully self-contained: standalone controller (does not extend the app base
 * Controller), its own views/assets/data. Nothing here writes to the database
 * besides the file-backed submission store, so the module can be added or
 * removed without side effects.
 *
 * Progress is NOT cached: nothing is persisted to the session while filling in
 * the wizard, and the page always renders fresh. Only a completed profile is
 * recorded (on submit) as a CRM website submission.
 *
 * Referral partners: /profiler?partner=CODE marks the visit as coming through a
 * partner's link. The code is handed to the wizard, comes back with the submit
 * payload, and on submit credits the lead to that partner and notifies their
 * mailbox alongside our own. An unknown or paused code changes nothing.
 *
 *   GET  /profiler  → renders the wizard (always a fresh start)
 *   POST /profiler  → JSON endpoint: action = submit records; save/reset are no-ops
 */
class StudentProfilerController
{
    public function __construct(private WebsiteLeadManager $leads)
    {
        View::addNamespace('student-profiler', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        // The code travels with the link, so it is read off the query string and
        // handed to the wizard, which echoes it back on submit. Resolved here as
        // well so an unknown or paused code is simply not carried at all.
        $partner = CrmPartnerCode::resolve($request->query('partner'));

        return View::make('student-profiler::wizard', [
            'config' => $this->config(),
            'partner' => $partner?->code,
            'state'  => [
                // Progress is not cached — the wizard always starts fresh.
                'degree'    => null,
                'section'   => 0,
                'answers'   => [],
                'contact'   => (object) [],
                'submitted' => false,
            ],
            'pageTitle'       => 'Student Profiler',
            'pageDescription' => 'Build your profile in minutes. Tell us your degree level, academics, test scores, preferences and aspirations — our advisors will personally review your profile and get back to you.',
            'activeNav'       => null,
            'bodyClass'       => 'sp-page',
        ]);
    }

    private function handle(Request $request): JsonResponse
    {
        $action = (string) $request->input('action', 'save');

        // No caching: 'save' / 'reset' are accepted (so the client never errors)
        // but persist nothing. Only 'submit' does any work.
        if ($action !== 'submit') {
            return response()->json(['ok' => true]);
        }

        $config  = $this->config();
        $degree  = $this->validDegree($request->input('degree'), $config);
        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            $answers = [];
        }
        $contact = $this->cleanContact($request->input('contact', []));

        // Placeholder addresses (anything containing "example") are undeliverable.
        // The relay accepts them, retries for hours, then bounces — so they are
        // refused here: no lead is captured and no mail is sent.
        if ($contact['email'] !== '' && str_contains(mb_strtolower($contact['email']), 'example')) {
            // Logged for the same reason the degree refusal is: a submit that
            // stores nothing should be answerable afterwards. Without this, a
            // refusal was indistinguishable from any other in the access log and
            // the only way to tell them apart was to ask the visitor.
            report(new \RuntimeException('Profiler submit refused: placeholder email ('.$contact['email'].').'));

            return response()->json([
                'ok'      => false,
                'field'   => 'email',
                // Naming the reason: the generic "use a valid email address" read
                // as the form being broken to anyone testing with example.com,
                // who then retried the same address and gave up.
                'message' => 'Placeholder addresses like example.com cannot receive your report. Please enter a real email address.',
            ], 422);
        }

        // Which partner's link this profile came through, if any. The wizard
        // sends back the code it was given; the query string is honoured too, so
        // a POST made straight to /profiler?partner=CODE works the same way.
        $partner = CrmPartnerCode::resolve($request->input('partner', $request->query('partner')));

        // A submit with no valid degree used to fall straight past the capture
        // below and still answer with the success message: thanked, nothing
        // stored, no mail, nothing logged.
        //
        // The wizard cannot reach this state on its own — renderWizard() runs
        // only from selectDegree(), so the submit button does not exist until a
        // degree is chosen, and there is no history handling that could restore
        // the review screen without one. This guards the other callers: a direct
        // POST, an integration, a future change to the client. Answering "ok" to
        // a request that stored nothing is the part worth refusing, whoever made
        // it, and report() means a real occurrence shows up in the log instead of
        // being invisible.
        if (! $degree) {
            report(new \RuntimeException(
                'Profiler submit refused: no valid degree (got '.var_export($request->input('degree'), true).').'
            ));

            return response()->json([
                'ok'      => false,
                'field'   => 'degree',
                'message' => 'Please pick your study level before submitting — reload the page and start from the first step.',
            ], 422);
        }

        // Record the completed profile as a human-readable snapshot for the
        // admin panel — no scoring is performed.
        $sections = WebsiteSubmissionData::snapshot($config['sections'][$degree] ?? [], $answers);

        $this->leads->capture(
            'profiler',
            'Student Profiler',
            $degree,
            $sections,
            $contact,
            partnerCode: $partner,
        );

        // Email a profile report to the team + a thank-you to the student,
        // and a referral notice to the partner when the link carried a code
        // (direct SMTP, no queue). Best-effort: never blocks the response.
        ProfileReportNotifier::notify(ProfileReportBuilder::build(
            'profiler',
            'Student Profiler',
            $config['degrees'][$degree]['label'] ?? null,
            $sections,
            $contact
        ), $partner);

        // No scoring/rating — the profile is handed to the team for a manual
        // review. We just confirm receipt.
        return response()->json([
            'ok'      => true,
            'message' => 'Thanks! Our team will get back to you with a detailed review of your profile.',
        ]);
    }

    private function validDegree($degree, array $config): ?string
    {
        return in_array($degree, $config['degreeOrder'], true) ? $degree : null;
    }

    /**
     * Normalise the lead-contact payload to exactly name/email/phone strings.
     * Stored in the submission's meta so the admin panel can follow up.
     *
     * @param  mixed  $contact
     * @return array{name:string,email:string,phone:string}
     */
    private function cleanContact($contact): array
    {
        $contact = is_array($contact) ? $contact : [];

        return [
            'name'  => trim((string) ($contact['name'] ?? '')),
            'email' => trim((string) ($contact['email'] ?? '')),
            'phone' => trim((string) preg_replace('/[^0-9+().\-\s]/', '', (string) ($contact['phone'] ?? ''))),
        ];
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__ . '/questionnaire.php';
    }
}
