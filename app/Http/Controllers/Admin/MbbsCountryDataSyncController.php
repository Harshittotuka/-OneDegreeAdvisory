<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MbbsCountryDataSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MbbsCountryDataSyncController extends Controller
{
    public function __construct(private MbbsCountryDataSync $sync)
    {
    }

    public function index(): View
    {
        return view('admin.country-sync.index', [
            'state' => $this->sync->state(),
            'tool' => [
                'title' => 'Sync MBBS countries',
                'source_label' => 'AV Global Overseas',
                'source_url' => 'https://www.avglobaloverseas.com/study-mbbs-abroad/mbbs-in-georgia',
                'description' => 'MBBS country pages, website JSON review, workbook, and change report.',
                'route_prefix' => 'admin.mbbs-country-sync',
                'apply_confirm' => 'Update live MBBS country data with the reviewed scrape?',
                'updated_message' => 'MBBS country data updated.',
                'selected_message' => 'selected MBBS change(s) applied to the website JSON.',
                'note' => 'Selected changes update the MBBS website JSON. The full Update all data action also replaces the workbook and scraper snapshot.',
            ],
        ]);
    }

    public function check(): RedirectResponse
    {
        try {
            @set_time_limit(0);
            $result = $this->sync->runReview();
            $summary = $result['comparison']['summary'] ?? null;
            $message = $summary
                ? "MBBS source checked: {$summary['changed_percent']}% changed across {$summary['changed_records']} record(s)."
                : 'MBBS source checked.';

            return redirect()->route('admin.mbbs-country-sync.index')->with('status', $message);
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->withErrors(['country_sync' => $this->shortError($e->getMessage())]);
        }
    }

    public function start(): JsonResponse
    {
        try {
            @set_time_limit(0);
            $result = $this->sync->startReview();

            return response()->json([
                'ok' => true,
                'started' => ! empty($result['started']),
                'already_running' => ! empty($result['already_running']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $this->shortError($e->getMessage()),
            ], 500);
        }
    }

    public function progress(): JsonResponse
    {
        try {
            return response()->json($this->sync->progress());
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'failed' => true,
                'percent' => 100,
                'message' => $this->shortError($e->getMessage()),
            ], 500);
        }
    }

    public function applyAll(): RedirectResponse
    {
        try {
            $backupDir = $this->sync->applyAll();

            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->with('status', 'MBBS country data updated. Backup saved at '.$backupDir.'.');
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->withErrors(['country_sync' => $this->shortError($e->getMessage())]);
        }
    }

    public function applySelected(Request $request): RedirectResponse
    {
        try {
            $applied = $this->sync->applySelected((array) $request->input('changes', []));

            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->with('status', "{$applied} selected MBBS change(s) applied to the website JSON.");
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->withErrors(['country_sync' => $this->shortError($e->getMessage())]);
        }
    }

    public function downloadReport(): BinaryFileResponse|RedirectResponse
    {
        $path = $this->sync->reviewReportPath();

        if (! is_file($path)) {
            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->withErrors(['country_sync' => 'No pending MBBS change report is available yet.']);
        }

        return response()->download($path);
    }

    public function downloadWorkbook(): BinaryFileResponse|RedirectResponse
    {
        $path = $this->sync->reviewWorkbookPath();

        if (! is_file($path)) {
            return redirect()
                ->route('admin.mbbs-country-sync.index')
                ->withErrors(['country_sync' => 'No pending MBBS review workbook is available yet.']);
        }

        return response()->download($path);
    }

    private function shortError(string $message): string
    {
        $message = trim($message);
        $lower = strtolower($message);

        if (str_contains($lower, 'getaddrinfo')
            || str_contains($lower, 'name resolution')
            || str_contains($lower, 'max retries exceeded')
            || str_contains($lower, 'could not fetch')
            || str_contains($lower, 'connectionerror')
        ) {
            return 'Could not reach avglobaloverseas.com from the server. Check internet/DNS access, then click Check source changes again.';
        }

        return strlen($message) > 900 ? substr($message, 0, 900).'...' : $message;
    }
}
