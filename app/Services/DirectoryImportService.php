<?php

namespace App\Services;

use App\Models\DirectoryContact;

class DirectoryImportService
{
    /** @return array{imported: int, skipped: int, errors: list<string>} */
    public function importFromCsv(string $path, int $userId): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Dosya okunamadı.']];
        }

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);

            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Boş dosya.']];
        }

        $header = array_map(fn ($h) => $this->normalizeHeader((string) $h), $header);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = $this->mapRow($header, $row);

            if (empty($data['phone'])) {
                $skipped++;
                $errors[] = "Satır {$line}: telefon zorunlu.";

                continue;
            }

            DirectoryContact::create([
                'first_name' => $data['first_name'] ?: '—',
                'last_name' => $data['last_name'] ?: '',
                'phone' => $data['phone'],
                'description' => $data['description'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }

    protected function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return match ($header) {
            'ad', 'isim', 'first_name', 'firstname' => 'first_name',
            'soyad', 'soyisim', 'last_name', 'lastname' => 'last_name',
            'telefon', 'tel', 'phone', 'gsm' => 'phone',
            'açıklama', 'aciklama', 'not', 'description' => 'description',
            default => $header,
        };
    }

    /** @param list<string|null> $row */
    protected function mapRow(array $header, array $row): array
    {
        $mapped = [
            'first_name' => '',
            'last_name' => '',
            'phone' => '',
            'description' => null,
        ];

        foreach ($header as $i => $key) {
            $value = trim((string) ($row[$i] ?? ''));
            if ($value === '' || ! array_key_exists($key, $mapped)) {
                continue;
            }
            $mapped[$key] = $value;
        }

        if ($mapped['first_name'] === '' && $mapped['last_name'] === '' && isset($row[0])) {
            $parts = preg_split('/\s+/', trim((string) $row[0]), 2);
            $mapped['first_name'] = $parts[0] ?? '';
            $mapped['last_name'] = $parts[1] ?? '';
        }

        return $mapped;
    }
}
