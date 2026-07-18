<?php

namespace App\Support;

use InvalidArgumentException;
use PhpZip\ZipFile;
use SimpleXMLElement;

/**
 * Reads the small, transposed .xlsx format used by the university shortlisting
 * team: column A contains the attribute labels and columns B onward contain
 * one university option each.
 *
 * This intentionally reads only the OOXML parts needed for cell values. It
 * uses a pure-PHP ZIP reader so the CMS tool also works on hosts where ext-zip
 * is unavailable.
 */
final class ShortlistingWorkbook
{
    private const SPREADSHEET_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @return array{
     *     sheet: string,
     *     country: string,
     *     options: array<int, string>,
     *     rows: array<int, array{label: string, values: array<int, string>, is_url: bool}>,
     *     character_count: int
     * }
     */
    public function read(string $path): array
    {
        if (! is_file($path) || filesize($path) === 0) {
            throw new InvalidArgumentException('The Excel file is empty or could not be read.');
        }

        $zip = new ZipFile;

        try {
            $zip->openFile($path);
            $workbook = $this->xml($zip, 'xl/workbook.xml');
            $relationships = $this->relationships($zip);
            $sharedStrings = $this->sharedStrings($zip);

            $workbook->registerXPathNamespace('s', self::SPREADSHEET_NS);
            $sheets = $workbook->xpath('/s:workbook/s:sheets/s:sheet') ?: [];

            if ($sheets === []) {
                throw new InvalidArgumentException('Excel format issue: the workbook contains no worksheets.');
            }

            $candidates = [];
            $sheetIssues = [];
            $visibleSheetCount = 0;

            foreach ($sheets as $sheet) {
                $attributes = $sheet->attributes();
                $relationshipAttributes = $sheet->attributes(self::OFFICE_REL_NS);
                $relationshipId = trim((string) ($relationshipAttributes['id'] ?? ''));
                $name = trim((string) ($attributes['name'] ?? ''));
                $state = strtolower(trim((string) ($attributes['state'] ?? 'visible')));

                if ($state === 'hidden') {
                    continue;
                }

                $visibleSheetCount++;

                if ($relationshipId === '' || ! isset($relationships[$relationshipId])) {
                    $sheetIssues[] = 'Sheet "'.($name !== '' ? $name : 'Unnamed sheet').'" is missing its worksheet relationship.';

                    continue;
                }

                try {
                    $matrix = $this->sheetMatrix($zip, $relationships[$relationshipId], $sharedStrings);
                } catch (InvalidArgumentException $exception) {
                    $sheetIssues[] = 'Sheet "'.($name !== '' ? $name : 'Unnamed sheet').'" could not be read: '.$exception->getMessage();

                    continue;
                }

                $candidate = $this->normaliseCandidate($name, $matrix);

                if ($candidate !== null) {
                    $candidate['score'] = count($candidate['rows']) * 100 + count($candidate['options']) * 10;

                    if (preg_match('/^(headings?|instructions?|read\s*me)$/i', $name)) {
                        $candidate['score'] -= 10000;
                    }

                    $candidates[] = $candidate;
                } else {
                    $sheetIssues[] = $this->sheetIssue($name, $matrix);
                }
            }

            if ($candidates === []) {
                if ($visibleSheetCount === 0) {
                    throw new InvalidArgumentException('Excel format issue: all worksheets are hidden. Make the shortlist sheet visible and try again.');
                }

                $detail = $sheetIssues[0] ?? 'No usable shortlist table was found.';

                throw new InvalidArgumentException('Excel format issue: '.$detail.' Put attribute names in column A and university options in column B onward.');
            }

            usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);
            $shortlist = $candidates[0];
            unset($shortlist['score']);

            return $shortlist;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException(
                'Excel file issue: the uploaded file could not be opened as an .xlsx workbook. It may be damaged, password-protected, or saved in the old .xls format. Open it in Excel, choose Save As > Excel Workbook (.xlsx), and try again.',
                previous: $exception
            );
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, string> */
    private function relationships(ZipFile $zip): array
    {
        $xml = $this->xml($zip, 'xl/_rels/workbook.xml.rels');
        $xml->registerXPathNamespace('r', self::PACKAGE_REL_NS);
        $relationships = [];

        foreach ($xml->xpath('/r:Relationships/r:Relationship') ?: [] as $relationship) {
            $attributes = $relationship->attributes();
            $id = trim((string) ($attributes['Id'] ?? ''));
            $target = trim((string) ($attributes['Target'] ?? ''));

            if ($id !== '' && $target !== '') {
                $relationships[$id] = $this->normaliseArchivePath(
                    str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target
                );
            }
        }

        return $relationships;
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipFile $zip): array
    {
        if (! $zip->hasEntry('xl/sharedStrings.xml')) {
            return [];
        }

        $xml = $this->xml($zip, 'xl/sharedStrings.xml');
        $xml->registerXPathNamespace('s', self::SPREADSHEET_NS);
        $strings = [];

        foreach ($xml->xpath('/s:sst/s:si') ?: [] as $item) {
            $strings[] = $this->joinedText($item);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function sheetMatrix(ZipFile $zip, string $entry, array $sharedStrings): array
    {
        $xml = $this->xml($zip, $entry);
        $xml->registerXPathNamespace('s', self::SPREADSHEET_NS);
        $matrix = [];

        foreach ($xml->xpath('/s:worksheet/s:sheetData/s:row') ?: [] as $row) {
            $row->registerXPathNamespace('s', self::SPREADSHEET_NS);
            $values = [];
            $nextColumn = 0;

            foreach ($row->xpath('./s:c') ?: [] as $cell) {
                $attributes = $cell->attributes();
                $reference = strtoupper((string) ($attributes['r'] ?? ''));
                $type = (string) ($attributes['t'] ?? '');
                $column = preg_match('/^([A-Z]+)/', $reference, $match)
                    ? $this->columnIndex($match[1])
                    : $nextColumn;

                $values[$column] = $this->cellValue($cell, $type, $sharedStrings);
                $nextColumn = $column + 1;
            }

            if ($values === []) {
                continue;
            }

            $lastColumn = max(array_keys($values));
            $normalised = [];
            for ($column = 0; $column <= $lastColumn; $column++) {
                $normalised[] = $this->cleanValue($values[$column] ?? '');
            }

            while ($normalised !== [] && end($normalised) === '') {
                array_pop($normalised);
            }

            if ($normalised !== []) {
                $matrix[] = $normalised;
            }
        }

        return $matrix;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, string $type, array $sharedStrings): string
    {
        $cell->registerXPathNamespace('s', self::SPREADSHEET_NS);

        if ($type === 'inlineStr') {
            $inline = $cell->xpath('./s:is');

            return isset($inline[0]) ? $this->joinedText($inline[0]) : '';
        }

        $valueNodes = $cell->xpath('./s:v');
        $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

        return match ($type) {
            's' => $sharedStrings[(int) $value] ?? '',
            'b' => $value === '1' ? 'TRUE' : 'FALSE',
            'e' => '',
            default => $value,
        };
    }

    private function joinedText(SimpleXMLElement $node): string
    {
        $node->registerXPathNamespace('s', self::SPREADSHEET_NS);
        $parts = $node->xpath('.//s:t') ?: [];

        return implode('', array_map(fn (SimpleXMLElement $part): string => (string) $part, $parts));
    }

    /**
     * @param  array<int, array<int, string>>  $matrix
     * @return array<string, mixed>|null
     */
    private function normaliseCandidate(string $sheetName, array $matrix): ?array
    {
        if ($matrix === []) {
            return null;
        }

        $maxColumns = max(array_map('count', $matrix));
        if ($maxColumns < 2) {
            return null;
        }

        $optionColumns = [];
        for ($column = 1; $column < $maxColumns; $column++) {
            foreach ($matrix as $row) {
                if (($row[$column] ?? '') !== '') {
                    $optionColumns[] = $column;
                    break;
                }
            }
        }

        if ($optionColumns === []) {
            return null;
        }

        $rows = [];
        $characterCount = 0;

        foreach ($matrix as $row) {
            $label = $this->cleanValue($row[0] ?? '');
            if ($label === '') {
                continue;
            }

            $values = array_map(fn (int $column): string => $this->cleanValue($row[$column] ?? ''), $optionColumns);
            if (count(array_filter($values, fn (string $value): bool => $value !== '')) === 0) {
                continue;
            }

            $characterCount += mb_strlen($label) + array_sum(array_map('mb_strlen', $values));
            $rows[] = [
                'label' => $label,
                'values' => $values,
                'is_url' => (bool) preg_match('/\b(url|website|link)\b/i', $label),
            ];
        }

        if ($rows === []) {
            return null;
        }

        $sheet = trim($sheetName) !== '' ? trim($sheetName) : 'Shortlist';

        return [
            'sheet' => $sheet,
            'country' => $this->countryFromSheet($sheet),
            'options' => array_map(fn (int $index): string => 'Option '.($index + 1), array_keys($optionColumns)),
            'rows' => $rows,
            'character_count' => $characterCount,
        ];
    }

    private function countryFromSheet(string $sheet): string
    {
        $country = trim((string) preg_replace('/\b(shortlist(?:ing)?|universit(?:y|ies)|options?|data)\b/i', '', $sheet));
        $country = trim((string) preg_replace('/\s+/', ' ', $country), " -_\t\n\r\0\x0B");

        return preg_match('/^(sheet\s*\d+|headings?)$/i', $country) ? '' : $country;
    }

    /** @param array<int, array<int, string>> $matrix */
    private function sheetIssue(string $sheetName, array $matrix): string
    {
        $sheet = trim($sheetName) !== '' ? trim($sheetName) : 'Unnamed sheet';

        if ($matrix === []) {
            return 'Sheet "'.$sheet.'" is empty.';
        }

        $maxColumns = max(array_map('count', $matrix));
        if ($maxColumns < 2) {
            return 'Sheet "'.$sheet.'" only contains column A; no university option columns were found.';
        }

        $hasOptionData = false;
        $hasLabel = false;
        foreach ($matrix as $row) {
            $hasLabel = $hasLabel || $this->cleanValue($row[0] ?? '') !== '';
            $hasOptionData = $hasOptionData || count(array_filter(
                array_slice($row, 1),
                fn (string $value): bool => $this->cleanValue($value) !== ''
            )) > 0;
        }

        if (! $hasLabel && $hasOptionData) {
            return 'Sheet "'.$sheet.'" has university data, but the attribute names in column A are blank.';
        }

        if (! $hasOptionData) {
            return 'Sheet "'.$sheet.'" has no university data in column B or later.';
        }

        return 'Sheet "'.$sheet.'" has no rows containing both an attribute name and university data.';
    }

    private function cleanValue(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $lines = array_map(fn (string $line): string => trim((string) preg_replace('/[ \t]+/', ' ', $line)), explode("\n", $value));

        return trim(implode("\n", $lines));
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function normaliseArchivePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    private function xml(ZipFile $zip, string $entry): SimpleXMLElement
    {
        if (! $zip->hasEntry($entry)) {
            throw new InvalidArgumentException(
                'The workbook is missing required Excel data. It may be damaged or may not be a genuine .xlsx file. Open it in Excel, choose Save As > Excel Workbook (.xlsx), and try again.'
            );
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($zip->getEntryContents($entry));
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $xml instanceof SimpleXMLElement) {
            throw new InvalidArgumentException(
                'The workbook contains damaged Excel data. Open it in Excel, choose Save As > Excel Workbook (.xlsx), and try again.'
            );
        }

        return $xml;
    }
}
