<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ProfileSubmissionStore;
use App\Support\SimpleXlsx;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
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

    /**
     * Download a single submission as a Word .doc whose layout mirrors the
     * on-screen Q&A cards (badges, contact block, per-section answer chips).
     * It is HTML-based, so Word / Google Docs open it and can save it as PDF.
     */
    public function download(string $id): Response|RedirectResponse
    {
        $submission = $this->store->find($id);
        if ($submission === null) {
            return redirect()->route('admin.submissions.profiler')->with('status', 'That submission no longer exists.');
        }

        $html = view('admin.submissions.doc', ['submission' => $submission])->render();

        $meta = is_array($submission['meta'] ?? null) ? $submission['meta'] : [];
        $name = trim((string) ($meta['name'] ?? ''));
        $base = Str::slug(($submission['source_label'] ?? 'profile').($name !== '' ? ' '.$name : '')) ?: 'profile-submission';
        $date = ! empty($submission['submitted_at']) ? date('Y-m-d', strtotime((string) $submission['submitted_at'])) : date('Y-m-d');
        $filename = $base.'-'.$date.'.doc';

        return response($html, 200, [
            'Content-Type'        => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
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
            fputcsv($out, ['Submitted at', 'Source', 'Name', 'Email', 'Phone', 'Degree', 'Section', 'Question', 'Answer']);
            foreach ($rows as $r) {
                $meta = is_array($r['meta'] ?? null) ? $r['meta'] : [];
                $lead = [
                    $r['submitted_at'] ?? '',
                    $r['source_label'] ?? ($r['source'] ?? ''),
                    $meta['name'] ?? '',
                    $meta['email'] ?? '',
                    $meta['phone'] ?? '',
                    $r['degree'] ?? '',
                ];

                $wrote = false;
                foreach (($r['sections'] ?? []) as $sec) {
                    foreach (($sec['answers'] ?? []) as $a) {
                        fputcsv($out, array_merge($lead, [
                            $sec['eyebrow'] ?? '',
                            $a['label'] ?? '',
                            implode(', ', (array) ($a['value'] ?? [])),
                        ]));
                        $wrote = true;
                    }
                }

                // A lead with no recorded answers still belongs in the export.
                if (! $wrote) {
                    fputcsv($out, array_merge($lead, ['', '', '']));
                }
            }
            fclose($out);
        }, 'profile-submissions'.$suffix.'-'.date('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Download submissions as a real .xlsx — one row per submission, each
     * distinct question becoming its own column (the same wide shape as the
     * admin "Table" view). Scoped to ?source=profiler|evaluator when given.
     */
    public function exportExcel(Request $request): Response
    {
        $source = (string) $request->query('source', '');
        $scoped = in_array($source, ['profiler', 'evaluator'], true);
        $rows = $scoped ? $this->store->bySource($source) : $this->store->all();
        $suffix = $scoped ? '-'.$source : '';

        $tab = ProfileSubmissionStore::tabulate($rows);

        $headers = array_merge(['Submitted at', 'Source', 'Name', 'Email', 'Phone', 'Degree'], $tab['questions']);
        $data = [];
        foreach ($tab['rows'] as $row) {
            $line = [$row['submitted_at'], $row['source_label'], $row['name'], $row['email'], $row['phone'], $row['degree']];
            foreach ($tab['questions'] as $q) {
                $line[] = $row['answers'][$q] ?? '';
            }
            $data[] = $line;
        }

        $sheet = $source === 'evaluator' ? 'Profile Evaluator' : ($source === 'profiler' ? 'Student Profiler' : 'Submissions');
        $xlsx = SimpleXlsx::build($headers, $data, $sheet);
        $filename = 'profile-submissions'.$suffix.'-'.date('Y-m-d').'.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
