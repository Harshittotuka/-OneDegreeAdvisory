<?php

namespace App\Modules\ProfileEvaluator;

use App\Support\ProfileReportBuilder;
use App\Support\ProfileReportNotifier;
use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Profile Evaluator — a native rebuild of the mim-essay "Evaluate My Profile"
 * questionnaire (6 sections, verbatim questions/options), wrapped in the same
 * animated wizard design as the Student Profiler.
 *
 * Fully self-contained: standalone controller (does not extend the app base
 * Controller), its own view / assets / data. Nothing here writes to the
 * database besides the file-backed submission store.
 *
 * Progress is NOT cached: nothing is persisted to the session while filling in
 * the wizard, and the page always renders fresh. Like the Student Profiler it
 * is NOT scored: on submit the profile is recorded and handed to the team for
 * a manual review and we just confirm receipt.
 *
 *   GET  /evaluate-my-profile  → renders the wizard (always a fresh start)
 *   POST /evaluate-my-profile  → JSON endpoint: action = submit records; save/reset are no-ops
 */
class ProfileEvaluatorController
{
    public function __construct()
    {
        View::addNamespace('profile-evaluator', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        return View::make('profile-evaluator::wizard', [
            'config' => $this->config(),
            'state'  => [
                // Progress is not cached — the wizard always starts fresh.
                'section'   => 0,
                'answers'   => [],
                'contact'   => (object) [],
                'submitted' => false,
            ],
            'pageTitle'       => 'Evaluate My Profile',
            'pageDescription' => 'The most comprehensive profile evaluation tool. Answer a few quick questions across academics, extracurriculars, work experience, test scores and your target degree — our advisors will personally review your profile and get back to you.',
            'activeNav'       => null,
            'bodyClass'       => 'pe-page',
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

        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            $answers = [];
        }
        $contact = $this->cleanContact($request->input('contact', []));

        // Record the completed evaluation as a human-readable snapshot for the
        // admin panel — no scoring is performed.
        $sections = ProfileSubmissionStore::snapshot($this->config()['sections'] ?? [], $answers);

        (new ProfileSubmissionStore())->add(
            'evaluator',
            'Profile Evaluator',
            null,
            $sections,
            $contact
        );

        // Email a profile report to the team + a thank-you to the student
        // (direct SMTP, no queue). Best-effort: never blocks the response.
        ProfileReportNotifier::notify(ProfileReportBuilder::build(
            'evaluator',
            'Profile Evaluator',
            null,
            $sections,
            $contact
        ));

        // No scoring/rating — the profile is handed to the team for a manual
        // review. We just confirm receipt (same as the Profiler).
        return response()->json([
            'ok'      => true,
            'message' => 'Thanks! Our team will get back to you with a detailed evaluation of your profile.',
        ]);
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
            'phone' => trim((string) ($contact['phone'] ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__ . '/questionnaire.php';
    }
}
