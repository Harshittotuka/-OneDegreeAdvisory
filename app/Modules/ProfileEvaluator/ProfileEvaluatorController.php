<?php

namespace App\Modules\ProfileEvaluator;

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
 * Controller), its own view / assets / data, session-only persistence. Nothing
 * here writes to the database or touches any existing feature, so the module
 * can be added or removed without side effects.
 *
 * Like the Student Profiler, it is NOT scored: on submit the profile is handed
 * to the team for a manual review and we just confirm receipt.
 *
 *   GET  /evaluate-my-profile  → renders the wizard (answers restored from session)
 *   POST /evaluate-my-profile  → JSON endpoint: action = save | submit | reset
 */
class ProfileEvaluatorController
{
    private const S_ANSWERS = 'evaluator.answers';
    private const S_SECTION = 'evaluator.section';
    private const S_DONE    = 'evaluator.submitted';

    public function __construct()
    {
        View::addNamespace('profile-evaluator', __DIR__ . '/views');
    }

    public function __invoke(Request $request): ViewContract|JsonResponse
    {
        if ($request->isMethod('post')) {
            return $this->handle($request);
        }

        $config = $this->config();

        return View::make('profile-evaluator::wizard', [
            'config' => $config,
            'state'  => [
                'section'   => $this->clampSection((int) $request->session()->get(self::S_SECTION, 0), $config),
                'answers'   => (array) $request->session()->get(self::S_ANSWERS, []),
                'submitted' => (bool) $request->session()->get(self::S_DONE, false),
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
        $config = $this->config();

        if ($action === 'reset') {
            $request->session()->forget([self::S_ANSWERS, self::S_SECTION, self::S_DONE]);

            return response()->json(['ok' => true]);
        }

        $answers = $request->input('answers', []);
        if (! is_array($answers)) {
            $answers = [];
        }

        $request->session()->put(self::S_ANSWERS, $answers);
        $request->session()->put(self::S_SECTION, $this->clampSection((int) $request->input('section', 0), $config));

        if ($action === 'submit') {
            // Record the completed evaluation once (guard against a re-POST after
            // the session is already marked done). Stored as a human-readable
            // snapshot for the admin panel — no scoring is performed.
            $alreadyDone = (bool) $request->session()->get(self::S_DONE, false);
            $request->session()->put(self::S_DONE, true);

            if (! $alreadyDone) {
                (new ProfileSubmissionStore())->add(
                    'evaluator',
                    'Profile Evaluator',
                    null,
                    ProfileSubmissionStore::snapshot($config['sections'] ?? [], $answers)
                );
            }

            // No scoring/rating — the profile is handed to the team for a
            // manual review. We just confirm receipt (same as the Profiler).
            return response()->json([
                'ok'      => true,
                'message' => 'Thanks! Our team will get back to you with a detailed evaluation of your profile.',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /** Clamp a requested section index into [0, sectionCount] (count == review step). */
    private function clampSection(int $section, array $config): int
    {
        $count = count($config['sections'] ?? []);

        return max(0, min($section, $count));
    }

    /** @return array<string, mixed> */
    private function config(): array
    {
        return require __DIR__ . '/questionnaire.php';
    }
}
