<?php

namespace App\Services\DocumentTools;

use FPDF;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;
use ZipArchive;

class PdfToolService
{
    public function merge(array $filePaths, string $outputPath): int
    {
        if (count($filePaths) < 2) {
            throw new RuntimeException(__('documents.tools.merge_min_files'));
        }

        $pdf = new Fpdi();
        $totalPages = 0;

        foreach ($filePaths as $path) {
            $pageCount = $pdf->setSourceFile($path);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                $totalPages++;
            }
        }

        $pdf->Output('F', $outputPath);

        return $totalPages;
    }

    /** @return list<string> Output file paths */
    public function split(string $sourcePath, string $outputDir, string $mode = 'pages', ?string $range = null): array
    {
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $probe = new Fpdi();
        $pageCount = $probe->setSourceFile($sourcePath);
        $outputs = [];

        if ($mode === 'range' && $range) {
            [$from, $to] = $this->parseRange($range, $pageCount);
            $output = $outputDir . '/split-' . $from . '-' . $to . '.pdf';
            $this->extractPages($sourcePath, $output, $from, $to);
            $outputs[] = $output;

            return $outputs;
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $output = $outputDir . '/page-' . str_pad((string) $pageNo, 3, '0', STR_PAD_LEFT) . '.pdf';
            $this->extractPages($sourcePath, $output, $pageNo, $pageNo);
            $outputs[] = $output;
        }

        return $outputs;
    }

    public function extractText(string $path): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);

        return trim($pdf->getText());
    }

    /** @param list<string> $imagePaths */
    public function imagesToPdf(array $imagePaths, string $outputPath): int
    {
        $pdf = new FPDF();
        $count = 0;

        foreach ($imagePaths as $imagePath) {
            $info = @getimagesize($imagePath);
            if ($info === false) {
                continue;
            }

            [$width, $height] = $info;
            $orientation = $width > $height ? 'L' : 'P';
            $pdf->AddPage($orientation);
            $pageW = $orientation === 'L' ? 297 : 210;
            $pageH = $orientation === 'L' ? 210 : 297;
            $margin = 10;
            $maxW = $pageW - ($margin * 2);
            $maxH = $pageH - ($margin * 2);
            $ratio = min($maxW / $width, $maxH / $height);
            $w = $width * $ratio;
            $h = $height * $ratio;
            $x = ($pageW - $w) / 2;
            $y = ($pageH - $h) / 2;
            $pdf->Image($imagePath, $x, $y, $w, $h);
            $count++;
        }

        if ($count === 0) {
            throw new RuntimeException(__('documents.tools.no_valid_images'));
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    protected function extractPages(string $sourcePath, string $outputPath, int $from, int $to): void
    {
        $pdf = new Fpdi();
        $pdf->setSourceFile($sourcePath);

        for ($pageNo = $from; $pageNo <= $to; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->Output('F', $outputPath);
    }

    /** @return array{0: int, 1: int} */
    protected function parseRange(string $range, int $maxPages): array
    {
        $range = trim($range);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $range, $m)) {
            $from = max(1, (int) $m[1]);
            $to = min($maxPages, (int) $m[2]);

            if ($from > $to) {
                throw new RuntimeException(__('documents.tools.invalid_range'));
            }

            return [$from, $to];
        }

        $page = max(1, min($maxPages, (int) $range));

        return [$page, $page];
    }
}
