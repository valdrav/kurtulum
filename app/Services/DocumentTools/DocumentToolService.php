<?php

namespace App\Services\DocumentTools;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DocumentToolService
{
    public function __construct(
        protected PdfToolService $pdf,
        protected SimpleXlsxWriter $xlsx,
        protected SimpleDocxWriter $docx,
        protected OfficeBridgeService $office,
    ) {}

    public function tempDir(): string
    {
        $dir = storage_path('app/temp/document-tools/' . auth()->id() . '/' . Str::uuid());
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    public function cleanup(?string $dir): void
    {
        if ($dir && is_dir($dir) && str_contains($dir, 'document-tools')) {
            File::deleteDirectory($dir);
        }
    }

    /** @param list<UploadedFile> $files */
    public function mergePdfs(array $files, string $filename = 'birlesik.pdf'): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $paths = $this->storeUploads($files, $dir);
        $output = $dir . '/merged.pdf';
        $this->pdf->merge($paths, $output);

        return $this->downloadAndCleanup($output, $filename, $dir);
    }

    public function splitPdf(UploadedFile $file, string $mode, ?string $range): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $source = $file->storeAs('temp/document-tools', Str::uuid() . '.pdf', 'local');
        $sourcePath = storage_path('app/' . $source);
        $outDir = $dir . '/split';
        $outputs = $this->pdf->split($sourcePath, $outDir, $mode, $range);

        if (count($outputs) === 1) {
            return $this->downloadAndCleanup($outputs[0], basename($outputs[0]), $dir);
        }

        $zipPath = $dir . '/split-pages.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($outputs as $path) {
            $zip->addFile($path, basename($path));
        }
        $zip->close();

        return $this->downloadAndCleanup($zipPath, 'pdf-sayfalar.zip', $dir);
    }

    public function pdfToWord(UploadedFile $file): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $stored = $file->storeAs('temp/document-tools', Str::uuid() . '.pdf', 'local');
        $path = storage_path('app/' . $stored);

        $officePath = $this->office->convert($path, 'docx', $dir);
        if ($officePath) {
            return $this->downloadAndCleanup($officePath, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.docx', $dir);
        }

        $text = $this->pdf->extractText($path);
        $output = $dir . '/converted.docx';
        $this->docx->fromText(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            $text !== '' ? $text : __('documents.tools.pdf_no_text'),
            $output
        );

        return $this->downloadAndCleanup($output, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.docx', $dir);
    }

    public function wordToPdf(UploadedFile $file): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $stored = $file->storeAs('temp/document-tools', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'local');
        $path = storage_path('app/' . $stored);
        $officePath = $this->office->convert($path, 'pdf', $dir);

        if (! $officePath) {
            $this->cleanup($dir);
            throw new RuntimeException(__('documents.tools.office_required'));
        }

        return $this->downloadAndCleanup($officePath, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.pdf', $dir);
    }

    /** @param list<UploadedFile> $files */
    public function imagesToPdf(array $files): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $paths = $this->storeUploads($files, $dir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $output = $dir . '/images.pdf';
        $this->pdf->imagesToPdf($paths, $output);

        return $this->downloadAndCleanup($output, 'gorseller.pdf', $dir);
    }

    public function pdfExtractText(UploadedFile $file): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $stored = $file->storeAs('temp/document-tools', Str::uuid() . '.pdf', 'local');
        $path = storage_path('app/' . $stored);
        $text = $this->pdf->extractText($path);
        $output = $dir . '/extracted.txt';
        file_put_contents($output, $text);

        return $this->downloadAndCleanup($output, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.txt', $dir);
    }

    public function createExcel(string $sheetName, string $rowsText): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $rows = $this->parseDelimitedRows($rowsText);
        $output = $dir . '/tablo.xlsx';
        $this->xlsx->write($rows, $output, $sheetName ?: 'Sayfa1');

        return $this->downloadAndCleanup($output, Str::slug($sheetName ?: 'tablo') . '.xlsx', $dir);
    }

    public function csvToExcel(UploadedFile $file): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $content = file_get_contents($file->getRealPath() ?: '');
        $rows = $this->parseDelimitedRows($content, $this->detectDelimiter($content));
        $output = $dir . '/converted.xlsx';
        $this->xlsx->write($rows, $output, 'Sayfa1');

        return $this->downloadAndCleanup($output, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.xlsx', $dir);
    }

    public function excelToCsv(UploadedFile $file): BinaryFileResponse
    {
        $dir = $this->tempDir();
        $stored = $file->storeAs('temp/document-tools', Str::uuid() . '.' . $file->getClientOriginalExtension(), 'local');
        $path = storage_path('app/' . $stored);

        $officePath = $this->office->convert($path, 'csv', $dir);
        if ($officePath) {
            return $this->downloadAndCleanup($officePath, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.csv', $dir);
        }

        throw new RuntimeException(__('documents.tools.excel_csv_office'));
    }

    /** @return list<list<string>> */
    protected function parseDelimitedRows(string $text, string $delimiter = "\t"): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text)) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $delimiter === 'tab' ? "\t" : $delimiter);
        }

        return $rows;
    }

    protected function detectDelimiter(string $content): string
    {
        $first = strtok($content, "\n") ?: '';
        $counts = [
            ',' => substr_count($first, ','),
            ';' => substr_count($first, ';'),
            "\t" => substr_count($first, "\t"),
        ];
        arsort($counts);

        return array_key_first($counts) ?: ',';
    }

    /** @param list<UploadedFile> $files @param list<string> $extensions */
    protected function storeUploads(array $files, string $dir, array $extensions = ['pdf']): array
    {
        $paths = [];
        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (! in_array($ext, $extensions, true)) {
                continue;
            }
            $target = $dir . '/' . Str::uuid() . '.' . $ext;
            $file->move(dirname($target), basename($target));
            $paths[] = $target;
        }

        if ($paths === []) {
            throw new RuntimeException(__('documents.tools.invalid_files'));
        }

        return $paths;
    }

    protected function downloadAndCleanup(string $path, string $downloadName, string $tempDir): BinaryFileResponse
    {
        register_shutdown_function(fn () => $this->cleanup($tempDir));

        return response()->download($path, $downloadName)->deleteFileAfterSend(true);
    }
}
