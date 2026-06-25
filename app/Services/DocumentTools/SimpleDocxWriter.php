<?php

namespace App\Services\DocumentTools;

use RuntimeException;
use ZipArchive;

class SimpleDocxWriter
{
    public function fromText(string $title, string $body, string $outputPath): void
    {
        $tmp = sys_get_temp_dir() . '/docx_' . uniqid('', true);
        mkdir($tmp . '/word', 0755, true);
        mkdir($tmp . '/_rels', 0755, true);

        file_put_contents($tmp . '/[Content_Types].xml', $this->contentTypes());
        file_put_contents($tmp . '/_rels/.rels', $this->rootRels());
        file_put_contents($tmp . '/word/_rels/document.xml.rels', $this->documentRels());
        file_put_contents($tmp . '/word/document.xml', $this->documentXml($title, $body));

        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException(__('documents.tools.zip_failed'));
        }

        $zip->addFile($tmp . '/[Content_Types].xml', '[Content_Types].xml');
        $zip->addFile($tmp . '/_rels/.rels', '_rels/.rels');
        $zip->addFile($tmp . '/word/document.xml', 'word/document.xml');
        $zip->addFile($tmp . '/word/_rels/document.xml.rels', 'word/_rels/document.xml.rels');
        $zip->close();

        @unlink($tmp . '/[Content_Types].xml');
        @unlink($tmp . '/_rels/.rels');
        @unlink($tmp . '/word/document.xml');
        @unlink($tmp . '/word/_rels/document.xml.rels');
        @rmdir($tmp . '/word/_rels');
        @rmdir($tmp . '/word');
        @rmdir($tmp . '/_rels');
        @rmdir($tmp);
    }

    protected function documentXml(string $title, string $body): string
    {
        $titleXml = $this->paragraph($title, true);
        $paragraphs = '';

        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                $paragraphs .= '<w:p/>';
                continue;
            }
            $paragraphs .= $this->paragraph($line, false);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $titleXml . $paragraphs . '</w:body></w:document>';
    }

    protected function paragraph(string $text, bool $bold): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $run = $bold
            ? '<w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">' . $escaped . '</w:t></w:r>'
            : '<w:r><w:t xml:space="preserve">' . $escaped . '</w:t></w:r>';

        return '<w:p>' . $run . '</w:p>';
    }

    protected function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML;
    }

    protected function rootRels(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    protected function documentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
    }
}
