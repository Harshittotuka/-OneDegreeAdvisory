<?php

namespace Tests\Feature;

use App\Models\CrmUser;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\Tfpdf\Fpdi;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class PdfShortlistingTest extends TestCase
{
    use RefreshDatabase;

    /** A non-admin CRM user — the tool is available to every signed-in member. */
    private function counsellor(): CrmUser
    {
        return CrmUser::query()->create([
            'name' => 'Counsellor',
            'phone' => '9876500000',
            'email' => 'counsellor@example.com',
            'role' => 'counsellor',
            'is_active' => true,
        ]);
    }

    public function test_pdf_shortlisting_requires_crm_authentication(): void
    {
        $this->post(route('crm.pdf-shortlisting.generate'))
            ->assertRedirect(route('crm.login'));
    }

    public function test_any_crm_user_can_open_the_shortlisting_view(): void
    {
        $this->withSession(['crm_user_id' => $this->counsellor()->id])
            ->get(route('crm.dashboard', ['view' => 'shortlisting']))
            ->assertOk()
            ->assertSee("Replace the report's last page", false)
            ->assertSee('Career report PDF')
            ->assertSee('University shortlist Excel');
    }

    public function test_pdf_and_excel_are_required(): void
    {
        $this->withSession(['crm_user_id' => $this->counsellor()->id])
            ->from(route('crm.dashboard', ['view' => 'shortlisting']))
            ->post(route('crm.pdf-shortlisting.generate'))
            ->assertRedirect(route('crm.dashboard', ['view' => 'shortlisting']))
            ->assertSessionHasErrors(['report_pdf', 'shortlist_excel'], null, 'shortlist');
    }

    public function test_it_replaces_only_the_last_page_and_returns_the_download(): void
    {
        $source = Pdf::loadHTML(<<<'HTML'
            <!doctype html><html><head><style>
              @page { margin: 40px; }
              body { font-family: DejaVu Sans, sans-serif; }
            </style></head><body>
              <div>ORIGINAL PAGE ONE<br>REPORT PREPARED FOR<br>Test Student</div>
              <div style="page-break-before: always">OLD LAST PAGE</div>
            </body></html>
            HTML)->setPaper('a4')->output();

        $workbook = SimpleXlsx::build(
            ['University Name', 'Example University', 'Second University'],
            [
                ['Program Name', 'MSc Computing', 'MSc Data Science'],
                ['Website URL', 'https://example.edu/computing', 'https://example.edu/data'],
                ['Location', 'New York, USA', 'Boston, USA'],
                ['Duration', '24 Months', '18 Months'],
            ],
            'USA'
        );

        $response = $this->withSession(['crm_user_id' => $this->counsellor()->id])
            ->post(route('crm.pdf-shortlisting.generate'), [
                'report_pdf' => UploadedFile::fake()->createWithContent('career-report.pdf', $source),
                'shortlist_excel' => UploadedFile::fake()->createWithContent('shortlist.xlsx', $workbook),
            ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('OneDegree-University-Shortlisting-test-student.pdf');

        $path = $response->baseResponse->getFile()->getPathname();

        try {
            $pdf = new Fpdi;
            $this->assertSame(2, $pdf->setSourceFile($path));
            $lastPage = $pdf->importPage(2);
            $lastPageSize = $pdf->getTemplateSize($lastPage);
            $this->assertGreaterThan($lastPageSize['height'], $lastPageSize['width']);

            $text = (new Parser)->parseFile($path)->getText();
            $this->assertStringContainsString('ORIGINAL PAGE ONE', $text);
            $this->assertStringNotContainsString('OLD LAST PAGE', $text);
            $this->assertStringContainsString('University Shortlisting - USA', $text);
            $this->assertStringContainsString('Example University', $text);
            $this->assertStringContainsString('Program comparison prepared for Test Student', $text);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function test_invalid_workbook_returns_a_clear_error(): void
    {
        $source = Pdf::loadHTML('<p>REPORT PREPARED FOR<br>Test Student</p>')->setPaper('a4')->output();

        $this->withSession(['crm_user_id' => $this->counsellor()->id])
            ->from(route('crm.dashboard', ['view' => 'shortlisting']))
            ->post(route('crm.pdf-shortlisting.generate'), [
                'report_pdf' => UploadedFile::fake()->createWithContent('career-report.pdf', $source),
                'shortlist_excel' => UploadedFile::fake()->createWithContent(
                    'shortlist.xlsx',
                    SimpleXlsx::build(['Only heading'], [], 'Headings')
                ),
            ])
            ->assertRedirect(route('crm.dashboard', ['view' => 'shortlisting']))
            ->assertSessionHasErrors([
                'merge' => 'Excel format issue: Sheet "Headings" only contains column A; no university option columns were found. Put attribute names in column A and university options in column B onward.',
            ], null, 'shortlist');
    }

    public function test_more_than_eight_university_options_are_allowed(): void
    {
        $source = Pdf::loadHTML('<p>REPORT PREPARED FOR<br>Many Options Student</p>')->setPaper('a4')->output();
        $universities = array_map(fn (int $number): string => 'University '.$number, range(1, 9));
        $programs = array_map(fn (int $number): string => 'Program '.$number, range(1, 9));
        $durations = array_fill(0, 9, '24 Months');
        $workbook = SimpleXlsx::build(
            ['University Name', ...$universities],
            [
                ['Program Name', ...$programs],
                ['Duration', ...$durations],
            ],
            'USA'
        );

        $response = $this->withSession(['crm_user_id' => $this->counsellor()->id])
            ->post(route('crm.pdf-shortlisting.generate'), [
                'report_pdf' => UploadedFile::fake()->createWithContent('career-report.pdf', $source),
                'shortlist_excel' => UploadedFile::fake()->createWithContent('shortlist.xlsx', $workbook),
            ]);

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $path = $response->baseResponse->getFile()->getPathname();

        try {
            $text = (new Parser)->parseFile($path)->getText();
            $this->assertStringContainsString('Option 9', $text);
            $this->assertStringContainsString('University 9', $text);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
