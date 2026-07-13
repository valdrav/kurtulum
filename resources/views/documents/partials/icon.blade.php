@props(['kind' => 'file', 'tone' => 'file', 'document' => null])

@php
    $resolvedTone = $kind === 'folder' ? 'folder' : ($document ? $document->iconTone() : $tone);
    $labels = [
        'folder' => 'K',
        'pdf' => 'PDF',
        'image' => 'IMG',
        'doc' => 'DOC',
        'xls' => 'XLS',
        'zip' => 'ZIP',
        'text' => 'TXT',
        'file' => 'DOS',
    ];
    $label = $labels[$resolvedTone] ?? 'DOS';
@endphp

<span class="doc-badge doc-badge-{{ $resolvedTone }}" aria-hidden="true">
    <span class="doc-badge-inner">{{ $label }}</span>
</span>
