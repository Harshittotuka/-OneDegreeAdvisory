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
 * Controller), its own views/assets/data, session-only persistence. Nothing
 * here writes to the database or touches any existing feature, so the module
 * can be added or removed without side effects.
 *
 *   GET  /profiler  → renders the wizard (degree + answers restored from session)
 *   POST /profiler  → JSON endpoint: action = save | submit | reset
 */
class StudentProfilerController
{
    private const S_ANSWERS = 'profiler.answers';
    private const S_DEGREE  = 'profiler.degree';
    private const S_SECTION = 'profiler.section';
    private const S_DONE    = 'profiler.submitted';
    private const S_CONTACT = 'profiler.contact';

    public function __construct()
    {
        View::addNamespace('student-profiler', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        $config = $this->config();

        return View::make('student-profiler::wizard', [
            'config' => $config,
            'state'  => [
                'degree'    => $this->validDegree($request->session()->get(self::S_DEGREE), $config),
                'section'   => (int) $request->session()->get(self::S_SECTION, 0),
                'answers'   => (array) $request->session()->get(self::S_ANSWERS, []),
                'contact'   => (object) $request->session()->get(self::S_CONTACT, []),
                'submitted' => (bool) $request->session()->get(self::S_DONE, false),
            ],
            'pageTitle'       => 'Student Profiler',
            'pageDescription' => 'Build your study-abroad profile in minutes. Tell us your degree level, academics, test scores, preferences and budget — get a tailored profile report and best-fit university shortlist.',
            'activeNav'       => null,
            'bodyClass'       => 'sp-page',
        ]);
    }

    private function handle(Request $request): JsonResponse
    {
        $action = (string) $request->input('action', 'save');
        $config = $this->config();

        if ($action === 'reset') {
            $request->session()->forget([self::S_ANSWERS, self::S_DEGREE, self::S_SECTION, self::S_DONE, self::S_CONTACT]);

            return response()->json(['ok' => true]);
        }

        $degree  = $this->validDegree($request->input('degree'), $config);
        $answers = $request->input('answers', []);
        $section = (int) $request->input('section', 0);

        if (! is_array($answers)) {
            $answers = [];
        }
        $contact = $this->cleanContact($request->input('contact', []));
        $sectionCount = $degree ? count($config['sections'][$degree] ?? []) : 0;
        $section = max(0, min($section, $sectionCount));

        $request->session()->put(self::S_DEGREE, $degree);
        $request->session()->put(self::S_ANSWERS, $answers);
        $request->session()->put(self::S_CONTACT, $contact);
        $request->session()->put(self::S_SECTION, $section);

        if ($action === 'submit') {
            // Record the completed profile once (guard against a re-POST after
            // the session is already marked done). Stored as a human-readable
            // snapshot for the admin panel — no scoring is performed.
            $alreadyDone = (bool) $request->session()->get(self::S_DONE, false);
            $request->session()->put(self::S_DONE, true);

            if (! $alreadyDone && $degree) {
                (new ProfileSubmissionStore())->add(
                    'profiler',
                    'Student Profiler',
                    $degree,
                    ProfileSubmissionStore::snapshot($config['sections'][$degree] ?? [], $answers),
                    $contact
                );
            }

            // No scoring/rating — the profile is handed to the team for a
            // manual review. We just confirm receipt.
            return response()->json([
                'ok'      => true,
                'message' => 'Thanks! Our team will get back to you with a detailed review of your profile.',
            ]);
        }

        return response()->json(['ok' => true]);
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
