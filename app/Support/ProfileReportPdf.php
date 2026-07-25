<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

/**
 * Renders the profile report (built by ProfileReportBuilder) into a formatted
 * PDF document, used as the attachment on both the team-notification and the
 * applicant thank-you emails. Pure PHP via dompdf — no external binary.
 *
 * The branded running chrome (header wordmark + orange rule, indigo footer
 * bar with "Page N of M") is drawn straight on the dompdf canvas AFTER layout,
 * so it can skip the cover page and number the content pages from 1 — the HTML
 * template only reserves the space via its @page margins.
 */
class ProfileReportPdf
{
    /** Render the report to raw PDF bytes. */
    public static function render(array $data): string
    {
        $pdf = Pdf::loadView('pdf.profile-report', ['data' => $data])
            ->setPaper('a4')
            // Subset embedded fonts so the attachment stays small while still
            // rendering Unicode glyphs (✓, curly quotes) the report relies on.
            ->setOption('isFontSubsettingEnabled', true);

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $w = $canvas->get_width();
        $h = $canvas->get_height();

        // Brand palette as 0–1 RGB for the canvas API.
        $ink    = [0.133, 0.122, 0.357]; // #221f5b
        $indigo = [0.102, 0.000, 0.533]; // #1a0088
        $orange = [1.000, 0.369, 0.196]; // #ff5e32
        $gray   = [0.604, 0.627, 0.702]; // #9aa0b3
        $white  = [1.0, 1.0, 1.0];
        $soft   = [0.816, 0.804, 0.910]; // lavender footer text

        $name = trim((string) ($data['name'] ?? '')) !== '' ? trim((string) $data['name']) : 'Applicant';

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($w, $h, $ink, $indigo, $orange, $gray, $white, $soft, $name) {
            // The cover page carries its own full-bleed art — no chrome there.
            if ($pageNumber === 1) {
                return;
            }

            $sans = $fontMetrics->getFont('DejaVu Sans');
            $bold = $fontMetrics->getFont('DejaVu Sans', 'bold');

            // ── Running header: wordmark left, applicant right, orange rule ──
            $canvas->text(30, 18, 'one', $bold, 17, $indigo);
            $ow = $fontMetrics->getTextWidth('one', $bold, 17);
            $canvas->text(30 + $ow + 1.5, 14.5, '°', $bold, 13, $orange);

            $nw = $fontMetrics->getTextWidth($name, $bold, 10);
            $canvas->text($w - 30 - $nw, 17, $name, $bold, 10, $ink);
            $cr = 'C A R E E R   R E P O R T';
            $cw = $fontMetrics->getTextWidth($cr, $sans, 5.5);
            $canvas->text($w - 30 - $cw, 30, $cr, $sans, 5.5, $gray);

            $canvas->line(30, 46, $w - 30, 46, $orange, 1.6);

            // ── Footer: full-width indigo bar with contact + page number ──
            $canvas->filled_rectangle(0, $h - 30, $w, 30, $ink);
            $brand = 'One Degree Advisory';
            $canvas->text(30, $h - 19.5, $brand, $bold, 7.5, $white);
            $bw = $fontMetrics->getTextWidth($brand, $bold, 7.5);
            $canvas->text(30 + $bw, $h - 19.5, '  |  Contact: +91 8233365888  |  counselling@onedegreeadvisory.com', $sans, 7.5, $soft);

            $pg = 'Page '.($pageNumber - 1).' of '.($pageCount - 1);
            $pw = $fontMetrics->getTextWidth($pg, $bold, 7.5);
            $canvas->text($w - 30 - $pw, $h - 19.5, $pg, $bold, 7.5, $white);

            // Second, smaller footer line: the AI-model disclaimer, centred.
            $disc = AiDisclaimer::TEXT;
            $dw = $fontMetrics->getTextWidth($disc, $sans, 6);
            $canvas->text(($w - $dw) / 2, $h - 10, $disc, $sans, 6, $soft);
        });

        return $dompdf->output();
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
