<?php

namespace App\Services\DocumentTools;

use RuntimeException;
use ZipArchive;

class SimpleXlsxWriter
{
    /** @param list<list<string|int|float|null>> $rows */
    public function write(array $rows, string $outputPath, string $sheetName = 'Sayfa1'): void
    {
        if ($rows === []) {
            throw new RuntimeException(__('documents.tools.excel_empty'));
        }

        $tmp = sys_get_temp_dir() . '/xlsx_' . uniqid('', true);
        mkdir($tmp, 0755, true);

        $sheetXml = $this->sheetXml($rows);
        $shared = $this->sharedStrings($rows);

        file_put_contents($tmp . '/[Content_Types].xml', $this->contentTypes());
        file_put_contents($tmp . '/_rels/.rels', $this->rootRels());
        file_put_contents($tmp . '/docProps/core.xml', $this->coreProps());
        file_put_contents($tmp . '/docProps/app.xml', $this->appProps($sheetName));
        file_put_contents($tmp . '/xl/workbook.xml', $this->workbookXml($sheetName));
        file_put_contents($tmp . '/xl/_rels/workbook.xml.rels', $this->workbookRels());
        file_put_contents($tmp . '/xl/styles.xml', $this->stylesXml());
        file_put_contents($tmp . '/xl/sharedStrings.xml', $shared['xml']);
        file_put_contents($tmp . '/xl/worksheets/sheet1.xml', $sheetXml);

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(__('documents.tools.zip_failed'));
        }

        $this->addFolderToZip($zip, $tmp, '');
        $zip->close();

        $this->deleteDir($tmp);
    }

    /** @param list<list<string|int|float|null>> $rows */
    protected function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<sheetData>';

        foreach ($rows as $rIndex => $row) {
            $xml .= '<row r="' . ($rIndex + 1) . '">';
            foreach ($row as $cIndex => $cell) {
                $ref = $this->columnLetter($cIndex) . ($rIndex + 1);
                if (is_int($cell) || is_float($cell)) {
                    $xml .= '<c r="' . $ref . '"><v>' . $cell . '</v></c>';
                } else {
                    $text = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $text . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    /** @param list<list<string|int|float|null>> $rows @return array{xml: string} */
    protected function sharedStrings(array $rows): array
    {
        return ['xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"></sst>'];
    }

    protected function columnLetter(int $index): string
    {
        $letter = '';
        $i = $index + 1;
        while ($i > 0) {
            $mod = ($i - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $i = intdiv($i - 1, 26);
        }

        return $letter;
    }

    protected function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    protected function rootRels(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    protected function workbookRels(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>
XML;
    }

    protected function workbookXml(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    protected function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"></styleSheet>';
    }

    protected function coreProps(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Kurtulum Portal</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created></cp:coreProperties>';
    }

    protected function appProps(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            . '<Application>Kurtulum Portal</Application><SheetName>' . $name . '</SheetName></Properties>';
    }

    protected function addFolderToZip(ZipArchive $zip, string $folder, string $relative): void
    {
        $items = scandir($folder) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $folder . DIRECTORY_SEPARATOR . $item;
            $local = ltrim(str_replace('\\', '/', $relative . '/' . $item), '/');
            if (is_dir($path)) {
                $this->addFolderToZip($zip, $path, $local);
            } else {
                $zip->addFile($path, $local);
            }
        }
    }

    protected function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
