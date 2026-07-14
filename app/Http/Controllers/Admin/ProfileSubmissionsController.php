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
        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => 'profiler',
            'submissions' => $this->store->bySource('profiler'),
        ]);
    }

    /** Loan & Acco enquiry list (from /loan-accommodation). */
    public function loanAcco(): View
    {
        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => 'loan-acco',
            'submissions' => $this->store->bySource('loan-acco'),
            'tabName'     => 'Loan & Acco',
            'tabBlurb'    => 'Education-loan & accommodation enquiries (/loan-accommodation).',
            'emptyUrl'    => route('loan-acco.index'),
        ]);
    }

    /** Statement of Purpose enquiry list (from /statement-of-purpose). */
    public function sop(): View
    {
        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => 'sop',
            'submissions' => $this->store->bySource('sop'),
            'tabName'     => 'Statement of Purpose',
            'tabBlurb'    => 'Strategy-call requests from the SOP writing studio (/statement-of-purpose).',
            'emptyUrl'    => route('sop.index'),
        ]);
    }

    /** Visa Mock Interview "unlock full interview" leads (from /visa-mock-interview). */
    public function visaMock(): View
    {
        return view('admin.submissions.index', [
            'portal'      => 'admin',
            'source'      => 'visa-mock',
            'submissions' => $this->store->bySource('visa-mock'),
            'tabName'     => 'Visa Mock Interview',
            'tabBlurb'    => 'Leads captured when a candidate unlocks the full AI Visa Mock Interview (/visa-mock-interview).',
            'emptyUrl'    => route('visa-mock'),
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

        $route = match ($request->input('source')) {
            'loan-acco' => 'admin.submissions.loan-acco',
            'sop'       => 'admin.submissions.sop',
            'visa-mock' => 'admin.submissions.visa-mock',
            default     => 'admin.submissions.profiler',
        };

        return redirect()->route($route)->with('status', 'Submission removed.');
    }

    /** Only "profiler", "loan-acco", "sop" and "visa-mock" are real tabs; anything else → profiler. */
    private function resolveSource(Request $request): string
    {
        $source = $request->input('source');

        return in_array($source, ['loan-acco', 'sop', 'visa-mock'], true) ? $source : 'profiler';
    }

    /**
     * Download a submissions tab as a flat CSV (one row per answered question).
     */
    public function export(Request $request): StreamedResponse
    {
        $source = $this->resolveSource($request);
        $rows = $this->store->bySource($source);
        $suffix = '-'.$source;

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
        $source = $this->resolveSource($request);
        $rows = $this->store->bySource($source);

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

        $sheet = match ($source) {
            'loan-acco' => 'Loan & Acco',
            'sop'       => 'Statement of Purpose',
            'visa-mock' => 'Visa Mock Interview',
            default     => 'Student Profiler',
        };
        $xlsx = SimpleXlsx::build($headers, $data, $sheet);
        $filename = 'profile-submissions-'.$source.'-'.date('Y-m-d').'.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
