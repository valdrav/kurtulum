<?php

namespace App\Services;

class EmailHtmlRenderer
{
    public function prepareForDisplay(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $textOnly = trim(strip_tags($html));

        if ($textOnly === '') {
            return null;
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<\/?(iframe|object|embed|form|input|button|link|meta|base)\b[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/(<img\b(?![^>]*\bloading=)[^>]*)(>)/i', '$1 loading="lazy" referrerpolicy="no-referrer-when-downgrade"$2', $html) ?? $html;
        $html = preg_replace('/(<img\b[^>]*)\swidth\s*=\s*["\']?\d+["\']?/i', '$1', $html) ?? $html;
        $html = preg_replace('/(<img\b[^>]*)\sheight\s*=\s*["\']?\d+["\']?/i', '$1', $html) ?? $html;
        $html = preg_replace('/\ssrc\s*=\s*["\']\/\//i', ' src="https://', $html) ?? $html;

        return $html;
    }
}
