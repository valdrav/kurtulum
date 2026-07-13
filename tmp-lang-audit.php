<?php

$locales = ['tr', 'en', 'fr', 'de', 'ar'];
$base = __DIR__.'/lang';

function flatten(array $arr, string $prefix = ''): array
{
    $result = [];
    foreach ($arr as $k => $v) {
        $key = $prefix === '' ? $k : $prefix.'.'.$k;
        if (is_array($v)) {
            $result += flatten($v, $key);
        } else {
            $result[$key] = (string) $v;
        }
    }

    return $result;
}

$trFiles = glob($base.'/tr/*.php');
$trNames = array_map('basename', $trFiles);

echo "=== MISSING FILES ===\n";
foreach (['en', 'fr', 'de', 'ar'] as $loc) {
    $existing = array_map('basename', glob($base.'/'.$loc.'/*.php') ?: []);
    $missing = array_diff($trNames, $existing);
    if ($missing) {
        echo "$loc: ".implode(', ', $missing)."\n";
    }
}

echo "\n=== MISSING KEYS PER LOCALE ===\n";
foreach (['en', 'fr', 'de', 'ar'] as $loc) {
    $missingKeys = [];
    foreach ($trNames as $name) {
        $trPath = $base.'/tr/'.$name;
        $otherPath = $base.'/'.$loc.'/'.$name;
        if (! is_file($otherPath)) {
            $ta = flatten(include $trPath);
            foreach (array_keys($ta) as $k) {
                $missingKeys[] = str_replace('.php', '', $name).'.'.$k;
            }
            continue;
        }
        $ta = flatten(include $trPath);
        $tb = flatten(include $otherPath);
        foreach (array_keys($ta) as $k) {
            if (! array_key_exists($k, $tb)) {
                $missingKeys[] = str_replace('.php', '', $name).'.'.$k;
            }
        }
    }
    echo "$loc: ".count($missingKeys)." missing keys\n";
    if (count($missingKeys) <= 30) {
        foreach ($missingKeys as $k) {
            echo "  - $k\n";
        }
    } else {
        foreach (array_slice($missingKeys, 0, 15) as $k) {
            echo "  - $k\n";
        }
        echo "  ... and ".(count($missingKeys) - 15)." more\n";
    }
}

// Turkish text in non-tr files (heuristic: contains Turkish chars)
echo "\n=== TURKISH TEXT IN NON-TR (sample) ===\n";
$turkishPattern = '/[ğüşıöçĞÜŞİÖÇ]|(?:tion|ment|eur|aux)\b/i';
foreach (['en', 'fr', 'de', 'ar'] as $loc) {
    $turkish = [];
    foreach (glob($base.'/'.$loc.'/*.php') ?: [] as $file) {
        $data = flatten(include $file);
        foreach ($data as $key => $val) {
            if (preg_match('/[ğüşıöçĞÜŞİÖÇ]/u', $val)) {
                $turkish[] = basename($file, '.php').'.'.$key.': '.mb_substr($val, 0, 60);
            }
        }
    }
    echo "$loc: ".count($turkish)." strings with Turkish chars\n";
    foreach (array_slice($turkish, 0, 8) as $line) {
        echo "  - $line\n";
    }
}
