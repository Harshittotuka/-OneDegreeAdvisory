<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Renders the profile report (built by ProfileReportBuilder) into a formatted
 * PDF document, used as the attachment on both the team-notification and the
 * applicant thank-you emails. Pure PHP via dompdf — no external binary.
 */
class ProfileReportPdf
{
    /** Render the report to raw PDF bytes. */
    public static function render(array $data): string
    {
        return Pdf::loadView('pdf.profile-report', ['data' => $data])
            ->setPaper('a4')
            // Subset embedded fonts so the attachment stays small while still
            // rendering Unicode glyphs (✓, curly quotes) the report relies on.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /** A friendly, filesystem-safe attachment filename for this report. */
    public static function filename(array $data): string
    {
        $name = Str::slug((string) ($data['name'] ?? ''));

        return $name !== ''
            ? 'OneDegree-Profile-Report-'.$name.'.pdf'
            : 'OneDegree-Profile-Report.pdf';
    }
}
