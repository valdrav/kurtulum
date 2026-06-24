@php
    $dark = $dark ?? false;
    $logoUrl = site_branding()->logoUrl($dark);
    $class = trim('ef-brand-mark ' . ($class ?? ''));
@endphp
@if($logoUrl)
<img src="{{ $logoUrl }}" alt="{{ app_brand() }}" class="{{ $class }}" loading="lazy">
@else
<span class="ef-brand-icon"><i class="ti ti-building-warehouse"></i></span>
@endif
