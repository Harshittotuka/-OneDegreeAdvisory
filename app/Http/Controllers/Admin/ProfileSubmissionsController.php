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
 * Admin viewer for the questionnaire submissions collected from the Student
 * Profiler (/profiler). Read-only review plus delete and CSV/Excel export —
 * backed by ProfileSubmissionStore.
 */
class ProfileSubmissionsController extends Controller
{
    public function __construct(private ProfileSubmissionStore $store)
    {
    }

    /** Bare /admin/submissions → the Student Profiler list. */
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.submissions.profiler');
    }

    /** Student Profiler submissions list. */
    public function profiler(): View
    {
        $all = $this->store->all();

        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => 'profiler',
            'submissions' => array_values(array_filter($all, fn ($r) => ($r['source'] ?? '') === 'profiler')),
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

        if ($id !== '') {
            $this->store->delete($id);
        }

        return redirect()->route('admin.submissions.profiler')->with('status', 'Submission removed.');
    }

    /**
     * Download the Student Profiler submissions as a flat CSV (one row per
     * answered question).
     */
    public function export(Request $request): StreamedResponse
    {
        $rows = $this->store->bySource('profiler');
        $suffix = '-profiler';

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
     * Download the Student Profiler submissions as a real .xlsx — one row per
     * submission, each distinct question becoming its own column (the same wide
     * shape as the admin "Table" view).
     */
    public function exportExcel(Request $request): Response
    {
        $rows = $this->store->bySource('profiler');

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

        $xlsx = SimpleXlsx::build($headers, $data, 'Student Profiler');
        $filename = 'profile-submissions-profiler-'.date('Y-m-d').'.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
