<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Support\PdfLastPageReplacer;
use App\Support\PdfStudentName;
use App\Support\ShortlistingWorkbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Report-production tool inside the CRM: replaces a career-report PDF's last
 * page with a branded, landscape comparison table built from a
 * university-shortlist Excel. Available to every signed-in CRM user — the GET
 * page is the dashboard's "shortlisting" view; this controller handles the
 * upload → merge → download. (Moved here from the admin CMS.)
 */
final class CrmPdfShortlistingController extends Controller
{
    public function generate(
        Request $request,
        ShortlistingWorkbook $workbook,
        PdfStudentName $studentName,
        PdfLastPageReplacer $replacer,
    ): BinaryFileResponse|RedirectResponse {
        // Errors go into the dedicated "shortlist" bag so they render in the
        // shortlisting view without tripping the CRM's default error toast.
        $validated = $request->validateWithBag('shortlist', [
            'report_pdf' => ['bail', 'required', 'file', 'mimetypes:application/pdf', 'max:25600'],
            'shortlist_excel' => [
                'bail',
                'required',
                'file',
                'extensions:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream',
                'max:5120',
            ],
        ], [
            'report_pdf.required' => 'Choose the PDF report to update.',
            'report_pdf.mimetypes' => 'The report must be a valid PDF file.',
            'report_pdf.max' => 'The PDF must be 25 MB or smaller.',
            'shortlist_excel.required' => 'Choose the Excel shortlist.',
            'shortlist_excel.extensions' => 'The shortlist must be an Excel Workbook (.xlsx), not an .xls or .csv file.',
            'shortlist_excel.mimetypes' => 'The file could not be recognized as a valid .xlsx workbook. Open it in Excel, choose Save As > Excel Workbook (.xlsx), and try again.',
            'shortlist_excel.max' => 'The Excel file must be 5 MB or smaller.',
        ]);

        $reportPath = $validated['report_pdf']->getRealPath();
        $workbookPath = $validated['shortlist_excel']->getRealPath();
        $directory = storage_path('app/private/tmp/shortlisting');
        File::ensureDirectoryExists($directory);
        $outputPath = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';

        try {
            $shortlist = $workbook->read($workbookPath);
            $name = $studentName->extract($reportPath);
            $replacer->replace($reportPath, $shortlist, $name, $outputPath);
        } catch (InvalidArgumentException $exception) {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }

            return redirect()->route('crm.dashboard', ['view' => 'shortlisting'])
                ->withErrors(['merge' => $exception->getMessage()], 'shortlist');
        } catch (\Throwable $exception) {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }

            report($exception);

            return redirect()->route('crm.dashboard', ['view' => 'shortlisting'])
                ->withErrors(['merge' => 'The files could not be merged. Please check both uploads and try again.'], 'shortlist');
        }

        $slug = Str::slug($name);
        $filename = 'OneDegree-University-Shortlisting'.($slug !== '' && $slug !== 'student' ? '-'.$slug : '').'.pdf';

        return response()->download($outputPath, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }
}
