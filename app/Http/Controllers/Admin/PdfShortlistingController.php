<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PdfLastPageReplacer;
use App\Support\PdfStudentName;
use App\Support\ShortlistingWorkbook;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PdfShortlistingController extends Controller
{
    public function index(): View
    {
        return view('admin.pdf-shortlisting.index');
    }

    public function generate(
        Request $request,
        ShortlistingWorkbook $workbook,
        PdfStudentName $studentName,
        PdfLastPageReplacer $replacer,
    ): BinaryFileResponse|RedirectResponse {
        $validated = $request->validate([
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

            return back()->withErrors([
                'merge' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }

            report($exception);

            return back()->withErrors([
                'merge' => 'The files could not be merged. Please check both uploads and try again.',
            ]);
        }

        $slug = Str::slug($name);
        $filename = 'OneDegree-University-Shortlisting'.($slug !== '' && $slug !== 'student' ? '-'.$slug : '').'.pdf';

        return response()->download($outputPath, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
        ])->deleteFileAfterSend(true);
    }
}
