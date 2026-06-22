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

    public function index(Request $request): View
    {
        $all = $this->store->all();

        // Optional source filter: ?source=profiler|evaluator (anything else = all).
        $source = (string) $request->query('source', '');
        $rows = in_array($source, ['profiler', 'evaluator'], true)
            ? array_values(array_filter($all, fn ($r) => ($r['source'] ?? '') === $source))
            : $all;

        return view('admin.submissions.index', [
            'submissions' => $rows,
            'source'      => $source,
            'counts'      => [
                'all'       => count($all),
                'profiler'  => count(array_filter($all, fn ($r) => ($r['source'] ?? '') === 'profiler')),
                'evaluator' => count(array_filter($all, fn ($r) => ($r['source'] ?? '') === 'evaluator')),
            ],
        ]);
    }

    public function show(string $id): View|RedirectResponse
    {
        $submission = $this->store->find($id);
        if ($submission === null) {
            return redirect()->route('admin.submissions.index')->with('status', 'That submission no longer exists.');
        }

        return view('admin.submissions.show', ['submission' => $submission]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $id = trim((string) $request->input('id', ''));
        if ($id !== '') {
            $this->store->delete($id);
        }

        return redirect()->route('admin.submissions.index')->with('status', 'Submission removed.');
    }

    /** Download every submission as a flat CSV (one row per answered question). */
    public function export(): StreamedResponse
    {
        $rows = $this->store->all();

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
        }, 'profile-submissions-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
