<?php

namespace App\Support;

final class AiDisclaimer
{
    /**
     * The single wording used on every generated report (profiler career report
     * PDF, the CRM shortlisting page appended to it, the career library report,
     * and the visa mock-interview report + its PDF). Kept in one place so the
     * sentence never drifts between surfaces.
     */
    public const TEXT = 'This report is based on an open-source AI model.';
}
