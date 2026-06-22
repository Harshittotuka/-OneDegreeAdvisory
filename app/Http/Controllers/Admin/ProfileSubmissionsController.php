<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin viewer for the questionnaire submissions collected from both the
 * Student Profiler (/profiler) and the Profile Evaluator (/evaluate-my-profile).
 * Read-only review plus delete and CSV export — backed by ProfileSubmissionStore.
 */
class ProfileSubmissionsController extends Controller
{
    public function __construct(private ProfileSubmissionStore $store)
    {
    }

    /** Bare /admin/submissions → default to the Student Profiler tab. */
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.submissions.profiler');
    }

    /** Student Profiler tab. */
    public function profiler(): View
    {
        return $this->tab('profiler');
    }

    /** Profile Evaluator tab. */
    public function evaluator(): View
    {
        return $this->tab('evaluator');
    }

    /** Render one source's submissions list. */
    private function tab(string $source): View
    {
        $all = $this->store->all();

        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => $source,
            'submissions' => array_values(array_filter($all, fn ($r) => ($r['source'] ?? '') === $source)),
            'counts'      => [
                'profiler'  => count(array_filter($all, fn ($r) => ($r['source'] ?? '') === 'profiler')),
                'evaluator' => count(array_filter($all, fn ($r) => ($r['source'] ?? '') === 'evaluator')),
            ],
        ]);
    }

    public function show(string $id): View|RedirectResponse
    {
        $submission = $this->store->find($id);
        if ($submission === null) {
            return redirect()->route('admin.submissions.profiler')->with('status', 'That submission no longer exists.');
        }

        return view('admin.submissions.show', ['portal' => 'admin', 'submission' => $submission]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $id = trim((string) $request->input('id', ''));

        // Return to the tab the deleted submission belonged to.
        $tab = 'admin.submissions.profiler';
        if ($id !== '') {
            $found = $this->store->find($id);
            if (($found['source'] ?? '') === 'evaluator') {
                $tab = 'admin.submissions.evaluator';
            }
            $this->store->delete($id);
        }

        return redirect()->route($tab)->with('status', 'Submission removed.');
    }

    /**
     * Download submissions as a flat CSV (one row per answered question).
     * Scoped to ?source=profiler|evaluator when given, otherwise all.
     */
    public function export(Request $request): StreamedResponse
    {
        $source = (string) $request->query('source', '');
        $scoped = in_array($source, ['profiler', 'evaluator'], true);
        $rows = $scoped ? $this->store->bySource($source) : $this->store->all();
        $suffix = $scoped ? '-'.$source : '';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Submitted at', 'Source', 'Degree', 'Section', 'Question', 'Answer']);
            foreach ($rows as $r) {
                $when   = $r['submitted_at'] ?? '';
                $src    = $r['source_label'] ?? ($r['source'] ?? '');
                $degree = $r['degree'] ?? '';
                foreach (($r['sections'] ?? []) as $sec) {
                    foreach (($sec['answers'] ?? []) as $a) {
                        fputcsv($out, [
                            $when,
                            $src,
                            $degree,
                            $sec['eyebrow'] ?? '',
                            $a['label'] ?? '',
                            implode(', ', (array) ($a['value'] ?? [])),
                        ]);
                    }
                }
            }
            fclose($out);
        }, 'profile-submissions'.$suffix.'-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
