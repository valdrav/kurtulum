<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /** @param iterable<int, array<string, scalar|null>> $rows */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_values($row), ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
