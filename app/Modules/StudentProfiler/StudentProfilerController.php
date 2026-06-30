<?php

namespace App\Modules\StudentProfiler;

use App\Support\ProfileSubmissionStore;
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
 * recorded (on submit) via the ProfileSubmissionStore.
 *
 *   GET  /profiler  → renders the wizard (always a fresh start)
 *   POST /profiler  → JSON endpoint: action = submit records; save/reset are no-ops
 */
class StudentProfilerController
{
    public function __construct()
    {
        View::addNamespace('student-profiler', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        return View::make('student-profiler::wizard', [
            'config' => $this->config(),
            'state'  => [
                // Progress is not cached — the wizard always starts fresh.
                'degree'    => null,
                'section'   => 0,
                'answers'   => [],
                'contact'   => (object) [],
                'submitted' => false,
            ],
            'pageTitle'       => 'Student Profiler',
            'pageDescription' => 'Build your study-abroad profile in minutes. Tell us your degree level, academics, test scores, preferences and budget — our advisors will personally review your profile and get back to you.',
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

        // Record the completed profile as a human-readable snapshot for the
        // admin panel — no scoring is performed.
        if ($degree) {
            (new ProfileSubmissionStore())->add(
                'profiler',
                'Student Profiler',
                $degree,
                ProfileSubmissionStore::snapshot($config['sections'][$degree] ?? [], $answers),
                $contact
            );
        }

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
            'phone' => trim((string) ($contact['phone'] ?? '')),
        ];
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__ . '/questionnaire.php';
    }
}
