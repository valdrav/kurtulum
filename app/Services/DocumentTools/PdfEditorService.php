<?php

namespace App\Services\DocumentTools;

use RuntimeException;

class PdfEditorService
{
    public function __construct(
        protected PdfToolService $pdf,
    ) {}

    public function pageCount(string $sourcePath): int
    {
        $probe = new PdfEditorEngine();

        return $probe->setSourceFile($sourcePath);
    }

    /** @param array<string, mixed> $options */
    public function process(string $sourcePath, string $outputPath, array $options): int
    {
        $operation = $options['operation'] ?? '';

        return match ($operation) {
            'rotate' => $this->rotate($sourcePath, $outputPath, $options),
            'watermark' => $this->watermark($sourcePath, $outputPath, $options),
            'remove_pages' => $this->removePages($sourcePath, $outputPath, $options),
            'reorder' => $this->reorder($sourcePath, $outputPath, $options),
            'page_numbers' => $this->pageNumbers($sourcePath, $outputPath, $options),
            'header_footer' => $this->headerFooter($sourcePath, $outputPath, $options),
            'compress' => $this->compress($sourcePath, $outputPath),
            'fit_a4' => $this->fitToA4($sourcePath, $outputPath, $options),
            default => throw new RuntimeException(__('documents.tools.editor.invalid_operation')),
        };
    }

    /** @param array<string, mixed> $options */
    protected function rotate(string $sourcePath, string $outputPath, array $options): int
    {
        $angle = (int) ($options['angle'] ?? 90);
        if (! in_array($angle, [90, 180, 270], true)) {
            throw new RuntimeException(__('documents.tools.editor.invalid_angle'));
        }

        $total = $this->pageCount($sourcePath);
        $pages = $this->resolvePages($sourcePath, $options['pages'] ?? 'all', $total);
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $count = 0;

        foreach ($pages as $pageNo) {
            $this->importPageWithRotation($pdf, $pageNo, $angle);
            $count++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    /** @param array<string, mixed> $options */
    protected function watermark(string $sourcePath, string $outputPath, array $options): int
    {
        $text = trim((string) ($options['text'] ?? ''));
        if ($text === '') {
            throw new RuntimeException(__('documents.tools.editor.watermark_required'));
        }

        $style = $options['style'] ?? 'diagonal';
        $total = $this->pageCount($sourcePath);
        $pages = $this->resolvePages($sourcePath, $options['pages'] ?? 'all', $total);
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $count = 0;

        foreach ($pages as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $w = $size['width'] ?? 210;
            $h = $size['height'] ?? 297;
            $orientation = $w > $h ? 'L' : 'P';
            $pdf->AddPage($orientation, [$w, $h]);
            $pdf->useTemplate($templateId);
            $this->drawWatermark($pdf, $text, $style, $w, $h);
            $count++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    /** @param array<string, mixed> $options */
    protected function removePages(string $sourcePath, string $outputPath, array $options): int
    {
        $removeSpec = trim((string) ($options['remove_pages'] ?? ''));
        if ($removeSpec === '') {
            throw new RuntimeException(__('documents.tools.editor.remove_required'));
        }

        $total = $this->pageCount($sourcePath);
        $toRemove = $this->pdf->parsePageList($removeSpec, $total);
        $keep = array_values(array_diff(range(1, $total), $toRemove));

        if ($keep === []) {
            throw new RuntimeException(__('documents.tools.editor.no_pages_left'));
        }

        return $this->exportPageList($sourcePath, $outputPath, $keep);
    }

    /** @param array<string, mixed> $options */
    protected function reorder(string $sourcePath, string $outputPath, array $options): int
    {
        $order = trim((string) ($options['order'] ?? ''));
        if ($order === '') {
            throw new RuntimeException(__('documents.tools.editor.order_required'));
        }

        $total = $this->pageCount($sourcePath);
        $pages = $this->pdf->parsePageList($order, $total);

        if ($pages === []) {
            throw new RuntimeException(__('documents.tools.editor.order_invalid'));
        }

        return $this->exportPageList($sourcePath, $outputPath, $pages);
    }

    /** @param array<string, mixed> $options */
    protected function pageNumbers(string $sourcePath, string $outputPath, array $options): int
    {
        $position = $options['position'] ?? 'bottom-center';
        $total = $this->pageCount($sourcePath);
        $pages = $this->resolvePages($sourcePath, $options['pages'] ?? 'all', $total);
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $count = 0;
        $index = 1;

        foreach ($pages as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $w = $size['width'] ?? 210;
            $h = $size['height'] ?? 297;
            $orientation = $w > $h ? 'L' : 'P';
            $pdf->AddPage($orientation, [$w, $h]);
            $pdf->useTemplate($templateId);
            $label = str_replace(['{n}', '{total}'], [(string) $index, (string) count($pages)], (string) ($options['format'] ?? '{n} / {total}'));
            $this->drawPageNumber($pdf, $label, $position, $w, $h);
            $count++;
            $index++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    /** @param array<string, mixed> $options */
    protected function headerFooter(string $sourcePath, string $outputPath, array $options): int
    {
        $header = trim((string) ($options['header'] ?? ''));
        $footer = trim((string) ($options['footer'] ?? ''));
        if ($header === '' && $footer === '') {
            throw new RuntimeException(__('documents.tools.editor.header_footer_required'));
        }

        $total = $this->pageCount($sourcePath);
        $pages = $this->resolvePages($sourcePath, $options['pages'] ?? 'all', $total);
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $count = 0;

        foreach ($pages as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $w = $size['width'] ?? 210;
            $h = $size['height'] ?? 297;
            $orientation = $w > $h ? 'L' : 'P';
            $pdf->AddPage($orientation, [$w, $h]);
            $pdf->useTemplate($templateId);
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(60, 60, 60);
            if ($header !== '') {
                $pdf->SetXY(10, 8);
                $pdf->Cell($w - 20, 5, $header, 0, 0, 'C');
            }
            if ($footer !== '') {
                $pdf->SetXY(10, $h - 14);
                $pdf->Cell($w - 20, 5, $footer, 0, 0, 'C');
            }
            $count++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    protected function compress(string $sourcePath, string $outputPath): int
    {
        $total = $this->pageCount($sourcePath);

        return $this->exportPageList($sourcePath, $outputPath, range(1, $total));
    }

    /** @param array<string, mixed> $options */
    protected function fitToA4(string $sourcePath, string $outputPath, array $options): int
    {
        $total = $this->pageCount($sourcePath);
        $pages = $this->resolvePages($sourcePath, $options['pages'] ?? 'all', $total);
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $a4w = 210.0;
        $a4h = 297.0;
        $margin = 10.0;
        $count = 0;

        foreach ($pages as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $sw = $size['width'] ?? $a4w;
            $sh = $size['height'] ?? $a4h;
            $orientation = $sw > $sh ? 'L' : 'P';
            $pageW = $orientation === 'L' ? $a4h : $a4w;
            $pageH = $orientation === 'L' ? $a4w : $a4h;
            $maxW = $pageW - ($margin * 2);
            $maxH = $pageH - ($margin * 2);
            $ratio = min($maxW / $sw, $maxH / $sh);
            $dw = $sw * $ratio;
            $dh = $sh * $ratio;
            $x = ($pageW - $dw) / 2;
            $y = ($pageH - $dh) / 2;
            $pdf->AddPage($orientation, [$pageW, $pageH]);
            $pdf->useTemplate($templateId, $x, $y, $dw, $dh);
            $count++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    /** @param list<int> $pages */
    protected function exportPageList(string $sourcePath, string $outputPath, array $pages): int
    {
        $pdf = new PdfEditorEngine();
        $pdf->setSourceFile($sourcePath);
        $count = 0;

        foreach ($pages as $pageNo) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            $w = $size['width'] ?? 210;
            $h = $size['height'] ?? 297;
            $orientation = $w > $h ? 'L' : 'P';
            $pdf->AddPage($orientation, [$w, $h]);
            $pdf->useTemplate($templateId);
            $count++;
        }

        $pdf->Output('F', $outputPath);

        return $count;
    }

    protected function importPageWithRotation(PdfEditorEngine $pdf, int $pageNo, int $angle): void
    {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $w = $size['width'] ?? 210;
        $h = $size['height'] ?? 297;

        if ($angle === 180) {
            $orientation = $w > $h ? 'L' : 'P';
            $pdf->AddPage($orientation, [$w, $h]);
            $pdf->rotateTransform(180, $w / 2, $h / 2);
            $pdf->useTemplate($templateId, -$w, -$h, $w, $h);
            $pdf->endTransform();

            return;
        }

        if ($angle === 90) {
            $pdf->AddPage($h > $w ? 'L' : 'P', [$h, $w]);
            $pdf->rotateTransform(90, 0, 0);
            $pdf->useTemplate($templateId, 0, -$h, $w, $h);
            $pdf->endTransform();

            return;
        }

        // 270
        $pdf->AddPage($h > $w ? 'L' : 'P', [$h, $w]);
        $pdf->rotateTransform(270, 0, 0);
        $pdf->useTemplate($templateId, -$w, 0, $w, $h);
        $pdf->endTransform();
    }

    protected function drawWatermark(PdfEditorEngine $pdf, string $text, string $style, float $w, float $h): void
    {
        $pdf->SetFont('Helvetica', 'B', $style === 'footer' ? 10 : 42);
        $pdf->SetTextColor(190, 190, 190);

        if ($style === 'center') {
            $pdf->SetXY(0, $h / 2);
            $pdf->Cell($w, 10, $text, 0, 0, 'C');

            return;
        }

        if ($style === 'footer') {
            $pdf->SetXY(10, $h - 18);
            $pdf->Cell($w - 20, 8, $text, 0, 0, 'C');

            return;
        }

        $pdf->rotateTransform(45, $w / 2, $h / 2);
        $pdf->SetXY($w * 0.15, $h * 0.45);
        $pdf->Cell($w * 0.7, 12, $text, 0, 0, 'C');
        $pdf->endTransform();
    }

    protected function drawPageNumber(PdfEditorEngine $pdf, string $label, string $position, float $w, float $h): void
    {
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(40, 40, 40);
        $align = str_contains($position, 'right') ? 'R' : (str_contains($position, 'left') ? 'L' : 'C');
        $x = str_contains($position, 'left') ? 10 : (str_contains($position, 'right') ? $w - 40 : 0);
        $y = str_contains($position, 'top') ? 10 : $h - 12;
        $pdf->SetXY($x, $y);
        $pdf->Cell(str_contains($position, 'center') ? $w : 30, 6, $label, 0, 0, $align);
    }

    /** @return list<int> */
    protected function resolvePages(string $sourcePath, string $spec, int $total): array
    {
        $spec = trim($spec);
        if ($spec === '' || $spec === 'all') {
            return range(1, $total);
        }

        return $this->pdf->parsePageList($spec, $total);
    }
}
