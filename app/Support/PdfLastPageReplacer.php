<?php

namespace App\Support;

use InvalidArgumentException;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\Tfpdf\Fpdi;

final class PdfLastPageReplacer
{
    /**
     * Replace the source PDF's final page with a one-page landscape shortlist.
     * Earlier pages are imported at their original dimensions and rotation.
     *
     * @param  array<string, mixed>  $shortlist
     */
    public function replace(string $sourcePath, array $shortlist, string $studentName, string $outputPath): void
    {
        $replacementPath = $outputPath.'.replacement.pdf';

        try {
            $pageCount = $this->pageCount($sourcePath, 'The uploaded PDF could not be read. It may be encrypted or damaged.');
            $densityLevels = $this->densityLevels($shortlist);
            $replacementReady = false;

            foreach ($densityLevels as $density) {
                file_put_contents(
                    $replacementPath,
                    UniversityShortlistingPdf::render($shortlist, $studentName, $pageCount, $density)
                );

                if ($this->pageCount($replacementPath, 'The replacement page could not be generated.') === 1) {
                    $replacementReady = true;
                    break;
                }
            }

            if (! $replacementReady) {
                throw new InvalidArgumentException(
                    'The Excel data is too large to fit on one landscape page. Shorten the longest cells or remove a few rows.'
                );
            }

            $pdf = new Fpdi;
            $pdf->SetAutoPageBreak(false);
            $sourcePageCount = $pdf->setSourceFile($sourcePath);

            for ($pageNumber = 1; $pageNumber < $sourcePageCount; $pageNumber++) {
                $this->appendPage($pdf, $pageNumber);
            }

            $pdf->setSourceFile($replacementPath);
            $this->appendPage($pdf, 1);
            $pdf->Output('F', $outputPath);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                'The PDF could not be merged. Please use a standard, non-password-protected PDF and try again.',
                previous: $exception
            );
        } finally {
            if (is_file($replacementPath)) {
                @unlink($replacementPath);
            }
        }
    }

    private function appendPage(Fpdi $pdf, int $pageNumber): void
    {
        $template = $pdf->importPage($pageNumber, PageBoundaries::CROP_BOX, true, true);
        $size = $pdf->getTemplateSize($template);
        $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
        $pdf->useTemplate($template, 0, 0, $size['width'], $size['height'], true);
    }

    private function pageCount(string $path, string $message): int
    {
        try {
            $count = (new Fpdi)->setSourceFile($path);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException($message, previous: $exception);
        }

        if ($count < 1) {
            throw new InvalidArgumentException($message);
        }

        return $count;
    }

    /** @param array<string, mixed> $shortlist */
    private function densityLevels(array $shortlist): array
    {
        $rows = count($shortlist['rows'] ?? []);
        $options = count($shortlist['options'] ?? []);
        $characters = (int) ($shortlist['character_count'] ?? 0);

        if ($rows > 13 || $options > 6 || $characters > 4200) {
            return ['ultra'];
        }

        if ($rows > 11 || $options > 5 || $characters > 2800) {
            return ['dense', 'ultra'];
        }

        return ['regular', 'dense', 'ultra'];
    }
}
