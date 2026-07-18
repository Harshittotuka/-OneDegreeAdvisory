<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;

final class UniversityShortlistingPdf
{
    /**
     * @param  array<string, mixed>  $shortlist
     */
    public static function render(array $shortlist, string $studentName, int $pageCount, string $density = 'regular'): string
    {
        return Pdf::loadView('pdf.university-shortlisting', [
            'shortlist' => $shortlist,
            'studentName' => $studentName,
            'pageCount' => $pageCount,
            'density' => $density,
        ])->setPaper('a4', 'landscape')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }
}
