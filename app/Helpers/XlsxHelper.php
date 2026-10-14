<?php

namespace App\Helpers;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

// Minimal .xlsx writer/reader (one sheet, text cells) built on ZipArchive, so
// the account generator can hand out and accept Excel files without a
// spreadsheet library.
class XlsxHelper
{
    // Write $headers + $rows to a temporary .xlsx file and return its path.
    // Every cell is stored as text so values (IDs, names) keep their exact form.
    public static function write(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $esc = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i): string {
            $s = '';
            for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
            return $s;
        };
        $row = function (int $r, array $values, int $style) use ($esc, $col): string {
            $cells = '';
            foreach (array_values($values) as $i => $v) {
                $cells .= '<c r="' . $col($i) . $r . '" t="inlineStr"' . ($style ? " s=\"{$style}\"" : '')
                    . '><is><t xml:space="preserve">' . $esc($v) . '</t></is></c>';
            }
            return "<row r=\"{$r}\">{$cells}</row>";
        };

        $xmlRows = [$row(1, $headers, 1)]; // style 1 = bold header
        foreach (array_values($rows) as $i => $values) $xmlRows[] = $row($i + 2, $values, 0);

        $widths = '';
        foreach (array_values($headers) as $i => $h) {
            $w = max(14, strlen((string) $h) + 4);
            foreach ($rows as $values) $w = max($w, strlen((string) (array_values($values)[$i] ?? '')) + 2);
            $widths .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min($w, 50) . '" customWidth="1"/>';
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . $esc($sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',
            // Style 0 = normal text, style 1 = bold. numFmt 49 = "@" (Text) so
            // typed IDs like 0012 aren't turned into numbers.
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
                . '<borders count="1"><border/></borders>'
                . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
                . '<cellXfs count="2"><xf fontId="0" numFmtId="49" applyNumberFormat="1"/><xf fontId="1" numFmtId="49" applyFont="1" applyNumberFormat="1"/></cellXfs>'
                . '</styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                . "<cols>{$widths}</cols>"
                . '<sheetData>' . implode('', $xmlRows) . '</sheetData></worksheet>',
        ];

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip  = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Could not create the Excel file.');
        foreach ($files as $name => $xml) $zip->addFromString($name, $xml);
        $zip->close();

        return $path;
    }

    // Read the first sheet of an .xlsx into rows of strings (row 1 = headers).
    // Handles shared strings, inline strings, numbers and blank (skipped) cells.
    public static function read(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('The file is not a valid Excel (.xlsx) file.');

        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (self::xml($xml)->si as $si) {
                // Plain <t>, or rich text split across <r><t> runs.
                $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map('strval', $si->xpath('.//*[local-name()="t"]')));
            }
        }

        $sheetXml = $zip->getFromName(self::firstSheetPath($zip));
        $zip->close();
        if ($sheetXml === false) throw new RuntimeException('The Excel file has no worksheet.');

        $rows = [];
        foreach (self::xml($sheetXml)->sheetData->row ?? [] as $row) {
            $values = [];
            foreach ($row->c as $c) {
                $index = self::columnIndex((string) $c['r']);
                $type  = (string) $c['t'];
                $value = match ($type) {
                    's'         => $shared[(int) $c->v] ?? '',
                    'inlineStr' => implode('', array_map('strval', $c->xpath('.//*[local-name()="t"]'))),
                    'b'         => ((string) $c->v) === '1' ? 'TRUE' : 'FALSE',
                    default     => (string) $c->v, // n, str, e
                };
                $values[$index] = $value;
            }
            if (!$values) continue;
            $line = array_fill(0, max(array_keys($values)) + 1, '');
            foreach ($values as $i => $v) $line[$i] = $v;
            $rows[] = $line;
        }
        return $rows;
    }

    private static function xml(string $xml): SimpleXMLElement
    {
        $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        if ($doc === false) throw new RuntimeException('The Excel file could not be read.');
        return $doc;
    }

    // Path of the first worksheet, via the workbook's relationships (Excel
    // doesn't always name it sheet1.xml).
    private static function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels     = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbook !== false && $rels !== false) {
            $wb  = self::xml($workbook);
            $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $sheet = $wb->xpath('//m:sheets/m:sheet')[0] ?? null;
            $rid   = $sheet ? (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '') : '';
            foreach (self::xml($rels)->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $target = ltrim((string) $rel['Target'], '/');
                    return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
                }
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    // "C7" → 2 (zero-based column).
    private static function columnIndex(string $ref): int
    {
        preg_match('/^[A-Z]+/', strtoupper($ref), $m);
        $n = 0;
        foreach (str_split($m[0] ?? 'A') as $ch) $n = $n * 26 + (ord($ch) - 64);
        return $n - 1;
    }
}
