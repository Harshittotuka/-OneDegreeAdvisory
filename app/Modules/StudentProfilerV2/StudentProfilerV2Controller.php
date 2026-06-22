<?php

namespace App\Modules\StudentProfilerV2;

use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Student Profiler V2 — the EXACT Student Profiler questionnaire (degree
 * adaptive, questions verbatim from /profiler) wrapped in the Profile
 * Evaluator's design.
 *
 * Behaviourally identical to the v1 StudentProfilerController: standalone
 * controller (does not extend the app base Controller), its own view/assets,
 * session-only persistence, no scoring. It shares v1's questionnaire definition
 * so the questions can never drift, but uses its OWN session keys so a session
 * on /profiler and /profiler-v2 never clobber each other.
 *
 *   GET  /profiler-v2  → renders the wizard (degree + answers restored from session)
 *   POST /profiler-v2  → JSON endpoint: action = save | submit | reset
 */
class StudentProfilerV2Controller
{
    private const S_ANSWERS = 'profiler_v2.answers';
    private const S_DEGREE  = 'profiler_v2.degree';
    private const S_SECTION = 'profiler_v2.section';
    private const S_DONE    = 'profiler_v2.submitted';

    public function __construct()
    {
        View::addNamespace('student-profiler-v2', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        $config = $this->config();

        return View::make('student-profiler-v2::wizard', [
            'config' => $config,
            'state'  => [
                'degree'    => $this->validDegree($request->session()->get(self::S_DEGREE), $config),
                'section'   => (int) $request->session()->get(self::S_SECTION, 0),
                'answers'   => (array) $request->session()->get(self::S_ANSWERS, []),
                'submitted' => (bool) $request->session()->get(self::S_DONE, false),
            ],
            'pageTitle'       => 'Student Profiler',
            'pageDescription' => 'Build your study-abroad profile in minutes. Tell us your degree level, academics, test scores, preferences and budget — our advisors will personally review your profile and get back to you.',
            'activeNav'       => null,
            'bodyClass'       => 'p2-page',
        ]);
    }

    private function handle(Request $request): JsonResponse
    {
        $action = (string) $request->input('action', 'save');
        $config = $this->config();

        if ($action === 'reset') {
            $request->session()->forget([self::S_ANSWERS, self::S_DEGREE, self::S_SECTION, self::S_DONE]);

            return response()->json(['ok' => true]);
        }

        $degree  = $this->validDegree($request->input('degree'), $config);
        $answers = $request->input('answers', []);
        $section = (int) $request->input('section', 0);

        if (! is_array($answers)) {
            $answers = [];
        }
        $sectionCount = $degree ? count($config['sections'][$degree] ?? []) : 0;
        $section = max(0, min($section, $sectionCount));

        $request->session()->put(self::S_DEGREE, $degree);
        $request->session()->put(self::S_ANSWERS, $answers);
        $request->session()->put(self::S_SECTION, $section);

        if ($action === 'submit') {
            // Record the completed profile once (guard against a re-POST after
            // the session is already marked done). Stored as a human-readable
            // snapshot for the admin panel — no scoring is performed. Recorded
            // under the same "profiler" source as v1 (so it lands in the Student
            // Profiler admin tab) but labelled so the two are distinguishable.
            $alreadyDone = (bool) $request->session()->get(self::S_DONE, false);
            $request->session()->put(self::S_DONE, true);

            if (! $alreadyDone && $degree) {
                (new ProfileSubmissionStore())->add(
                    'profiler',
                    'Student Profiler V2',
                    $degree,
                    ProfileSubmissionStore::snapshot($config['sections'][$degree] ?? [], $answers)
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

    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__ . '/questionnaire.php';
    }
}
