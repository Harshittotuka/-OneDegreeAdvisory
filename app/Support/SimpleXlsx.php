<?php

namespace App\Support;

/**
 * Minimal, dependency-free .xlsx (Office Open XML) writer.
 *
 * Builds a single-sheet workbook from a header row + data rows and packs it as
 * a real .xlsx that Excel / Google Sheets / LibreOffice open natively — without
 * the PhpSpreadsheet dependency AND without requiring the PHP zip extension
 * (the package is written as a "stored"/uncompressed ZIP by hand). Every cell
 * is written as an inline string, which is plenty for exporting questionnaire
 * answers and keeps the format dead simple.
 */
class SimpleXlsx
{
    /**
     * Build the raw .xlsx bytes.
     *
     * @param  array<int, string>             $headers  Header cells (first row).
     * @param  array<int, array<int, scalar>> $rows     Data rows.
     */
    public static function build(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $files = [
            '[Content_Types].xml'         => self::contentTypes(),
            '_rels/.rels'                 => self::rootRels(),
            'xl/workbook.xml'             => self::workbook($sheetName),
            'xl/_rels/workbook.xml.rels'  => self::workbookRels(),
            'xl/worksheets/sheet1.xml'    => self::sheet($headers, $rows),
        ];

        return self::zip($files);
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        // Sheet names: max 31 chars, none of []:*?/\ , and cannot be blank.
        $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $sheetName);
        $name = trim((string) $name);
        $name = $name === '' ? 'Sheet1' : mb_substr($name, 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc($name) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    private static function sheet(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $r = 1;
        $xml .= self::rowXml($r++, $headers);
        foreach ($rows as $row) {
            $xml .= self::rowXml($r++, array_values($row));
        }

        return $xml . '</sheetData></worksheet>';
    }

    private static function rowXml(int $r, array $cells): string
    {
        $out = '<row r="' . $r . '">';
        $col = 0;
        foreach ($cells as $val) {
            $ref  = self::colName($col++) . $r;
            $text = self::esc((string) $val);
            $out .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
        }

        return $out . '</row>';
    }

    /** 0 → A, 25 → Z, 26 → AA … */
    private static function colName(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) {
            $m = ($i - 1) % 26;
            $s = chr(65 + $m) . $s;
            $i = intdiv($i - 1, 26);
        }

        return $s;
    }

    private static function esc(string $v): string
    {
        // Strip control chars Excel rejects, then XML-escape.
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $v);

        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Pack the given path => contents map into a "stored" (uncompressed) ZIP.
     * Hand-rolled so it works without the PHP zip extension.
     *
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $local   = '';
        $central = '';
        $offset  = 0;
        $count   = 0;

        foreach ($files as $name => $content) {
            $crc     = crc32($content);
            $len     = strlen($content);
            $nameLen = strlen($name);

            $localHeader = "\x50\x4b\x03\x04"   // local file header signature
                . pack('v', 20)                  // version needed to extract
                . pack('v', 0)                   // general purpose flag
                . pack('v', 0)                   // compression method = stored
                . pack('v', 0)                   // mod time
                . pack('v', 0)                   // mod date
                . pack('V', $crc)
                . pack('V', $len)                // compressed size
                . pack('V', $len)                // uncompressed size
                . pack('v', $nameLen)
                . pack('v', 0)                   // extra field length
                . $name;

            $local .= $localHeader . $content;

            $central .= "\x50\x4b\x01\x02"       // central directory header signature
                . pack('v', 20)                  // version made by
                . pack('v', 20)                  // version needed
                . pack('v', 0)                   // general purpose flag
                . pack('v', 0)                   // compression = stored
                . pack('v', 0)                   // mod time
                . pack('v', 0)                   // mod date
                . pack('V', $crc)
                . pack('V', $len)                // compressed size
                . pack('V', $len)                // uncompressed size
                . pack('v', $nameLen)
                . pack('v', 0)                   // extra length
                . pack('v', 0)                   // comment length
                . pack('v', 0)                   // disk number start
                . pack('v', 0)                   // internal attributes
                . pack('V', 0)                   // external attributes
                . pack('V', $offset)             // local header offset
                . $name;

            $offset += strlen($localHeader) + $len;
            $count++;
        }

        $eocd = "\x50\x4b\x05\x06"               // end of central directory signature
            . pack('v', 0)                       // disk number
            . pack('v', 0)                       // disk with central dir
            . pack('v', $count)                  // entries on this disk
            . pack('v', $count)                  // total entries
            . pack('V', strlen($central))        // central dir size
            . pack('V', strlen($local))          // central dir offset
            . pack('v', 0);                      // comment length

        return $local . $central . $eocd;
    }
}
