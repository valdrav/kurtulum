@php
    $localIconsCss = public_path('vendor/tabler-icons/tabler-icons.min.css');
    $localIconsWoff2 = public_path('vendor/tabler-icons/fonts/tabler-icons.woff2');
    $useLocalIcons = is_file($localIconsCss) && is_file($localIconsWoff2);
    $cdnIconsCss = 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css';
@endphp
@if($useLocalIcons)
<link rel="preload" href="{{ asset('vendor/tabler-icons/fonts/tabler-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
<link href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}?v=3.3.0" rel="stylesheet">
@else
<link rel="preload" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/fonts/tabler-icons.woff2" as="font" type="font/woff2" crossorigin>
<link href="{{ $cdnIconsCss }}" rel="stylesheet">
@endif
{{-- CDN yedek: yerel font yüklenmezse toolbar ikonları yine görünsün --}}
<link href="{{ $cdnIconsCss }}" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="{{ $cdnIconsCss }}" rel="stylesheet"></noscript>
