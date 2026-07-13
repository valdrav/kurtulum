<?php

namespace App\Services;

class LangSyncService
{
    public function merge(array $master, array $existing, array $fallback = []): array
    {
        $result = [];

        foreach ($master as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->merge(
                    $value,
                    is_array($existing[$key] ?? null) ? $existing[$key] : [],
                    is_array($fallback[$key] ?? null) ? $fallback[$key] : [],
                );
            } elseif (isset($existing[$key]) && $existing[$key] !== '') {
                $result[$key] = $existing[$key];
            } elseif (isset($fallback[$key]) && $fallback[$key] !== '') {
                $result[$key] = $fallback[$key];
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @return array{filled: int, files: int, refreshed: int} */
    public function syncLocale(string $target, string $master = 'tr', ?string $fillFrom = null, ?callable $translateMissing = null, bool $refreshCopies = false): array
    {
        $masterPath = lang_path($master);
        $targetPath = lang_path($target);
        $fillPath = $fillFrom ? lang_path($fillFrom) : null;

        if (! is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $filled = 0;
        $refreshed = 0;
        $files = 0;

        foreach (glob($masterPath.'/*.php') ?: [] as $masterFile) {
            $name = basename($masterFile);
            /** @var array $masterData */
            $masterData = include $masterFile;
            $existing = is_file($targetPath.'/'.$name) ? (include $targetPath.'/'.$name) : [];
            $fallback = ($fillPath && is_file($fillPath.'/'.$name)) ? (include $fillPath.'/'.$name) : [];

            if (! is_array($existing)) {
                $existing = [];
            }
            if (! is_array($fallback)) {
                $fallback = [];
            }

            $before = $this->flatten($existing);
            $merged = $this->merge($masterData, $existing, $fallback);

            if ($translateMissing) {
                $merged = $this->applyTranslator($masterData, $merged, $existing, $fallback, $translateMissing, $filled);
            }

            if ($refreshCopies && $fillFrom) {
                $merged = $this->refreshUntranslatedCopies($masterData, $merged, $fallback, $refreshed);
            }

            $after = $this->flatten($merged);
            foreach ($after as $key => $value) {
                if (! isset($before[$key]) || $before[$key] === '') {
                    $filled++;
                }
            }

            $this->writePhpFile($targetPath.'/'.$name, $merged);
            $files++;
        }

        return ['filled' => $filled, 'files' => $files, 'refreshed' => $refreshed];
    }

    protected function refreshUntranslatedCopies(array $master, array $merged, array $fallback, int &$refreshed, string $prefix = ''): array
    {
        $result = [];

        foreach ($master as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->refreshUntranslatedCopies(
                    $value,
                    is_array($merged[$key] ?? null) ? $merged[$key] : [],
                    is_array($fallback[$key] ?? null) ? $fallback[$key] : [],
                    $refreshed,
                    $prefix === '' ? (string) $key : $prefix.'.'.$key,
                );
            } else {
                $current = (string) ($merged[$key] ?? $value);
                $source = (string) $value;
                $fill = (string) ($fallback[$key] ?? '');

                if ($current === $source && $fill !== '' && $fill !== $source) {
                    $result[$key] = $fill;
                    $refreshed++;
                } else {
                    $result[$key] = $current;
                }
            }
        }

        return $result;
    }

    protected function applyTranslator(array $master, array $merged, array $existing, array $fallback, callable $translate, int &$filled): array
    {
        return $this->mapRecursive($master, $merged, $existing, $fallback, $translate, $filled);
    }

    protected function mapRecursive(array $master, array $merged, array $existing, array $fallback, callable $translate, int &$filled, string $prefix = ''): array
    {
        $result = [];

        foreach ($master as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $result[$key] = $this->mapRecursive(
                    $value,
                    is_array($merged[$key] ?? null) ? $merged[$key] : [],
                    is_array($existing[$key] ?? null) ? $existing[$key] : [],
                    is_array($fallback[$key] ?? null) ? $fallback[$key] : [],
                    $translate,
                    $filled,
                    $path,
                );
            } else {
                $current = $merged[$key] ?? $value;
                $had = isset($existing[$key]) && $existing[$key] !== '';
                $hadFallback = isset($fallback[$key]) && $fallback[$key] !== '';

                if (! $had && ! $hadFallback && $current === $value) {
                    $translated = $translate($value, $path);
                    if ($translated !== $value) {
                        $filled++;
                    }
                    $result[$key] = $translated;
                } else {
                    $result[$key] = $current;
                }
            }
        }

        return $result;
    }

    public function flatten(array $data, string $prefix = ''): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                $result += $this->flatten($value, $path);
            } else {
                $result[$path] = (string) $value;
            }
        }

        return $result;
    }

    public function writePhpFile(string $path, array $data): void
    {
        file_put_contents($path, $this->exportArray($data));
    }

    public function exportArray(array $data, int $depth = 0): string
    {
        if ($depth === 0) {
            $out = "<?php\n\nreturn [\n";
        } else {
            $out = '';
        }

        $indent = str_repeat('    ', $depth + 1);
        $items = [];

        foreach ($data as $key => $value) {
            $keyExport = is_int($key) ? $key : "'".str_replace("'", "\\'", (string) $key)."'";

            if (is_array($value)) {
                $items[] = $indent.$keyExport." => ".$this->exportInlineOpen($value, $depth + 1);
            } else {
                $items[] = $indent.$keyExport.' => '.$this->exportScalar($value);
            }
        }

        if ($depth === 0) {
            return $out.implode(",\n", $items)."\n];\n";
        }

        return "[\n".implode(",\n", $items)."\n".str_repeat('    ', $depth).']';
    }

    protected function exportInlineOpen(array $data, int $depth): string
    {
        $inner = $this->exportArrayBody($data, $depth);

        return "[\n".$inner."\n".str_repeat('    ', $depth).']';
    }

    protected function exportArrayBody(array $data, int $depth): string
    {
        $indent = str_repeat('    ', $depth + 1);
        $items = [];

        foreach ($data as $key => $value) {
            $keyExport = is_int($key) ? $key : "'".str_replace("'", "\\'", (string) $key)."'";

            if (is_array($value)) {
                $items[] = $indent.$keyExport.' => '.$this->exportInlineOpen($value, $depth + 1);
            } else {
                $items[] = $indent.$keyExport.' => '.$this->exportScalar($value);
            }
        }

        return implode(",\n", $items);
    }

    protected function exportScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $value)."'";
    }
}
