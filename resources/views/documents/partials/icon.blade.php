@props(['kind' => 'file', 'tone' => 'file', 'document' => null])

@php
    $resolvedTone = $kind === 'folder' ? 'folder' : ($document ? $document->iconTone() : $tone);
    $iconClass = $document ? $document->iconClass() : 'ti-file';
@endphp

<span class="files-icon files-icon-{{ $resolvedTone }}" aria-hidden="true">
    @if($kind === 'folder')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z" fill="#3b82f6"/>
        <path d="M4 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v1H4V7z" fill="#60a5fa"/>
    </svg>
    @elseif($resolvedTone === 'pdf')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#fee2e2" stroke="#ef4444" stroke-width="1.5"/>
        <path d="M14 3v5h5" stroke="#ef4444" stroke-width="1.5"/>
        <text x="7.5" y="17" font-size="5" font-weight="700" fill="#ef4444">PDF</text>
    </svg>
    @elseif($resolvedTone === 'image')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="16" height="16" rx="2" fill="#f3e8ff" stroke="#9333ea" stroke-width="1.5"/>
        <circle cx="9" cy="10" r="1.5" fill="#9333ea"/>
        <path d="M6 17l4-4 3 3 2-2 3 3" stroke="#9333ea" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    @elseif($resolvedTone === 'doc')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#dbeafe" stroke="#2563eb" stroke-width="1.5"/>
        <path d="M14 3v5h5" stroke="#2563eb" stroke-width="1.5"/>
        <path d="M8 13h8M8 16h6" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @elseif($resolvedTone === 'xls')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#dcfce7" stroke="#16a34a" stroke-width="1.5"/>
        <path d="M14 3v5h5" stroke="#16a34a" stroke-width="1.5"/>
        <path d="M8 12h8v6H8z" stroke="#16a34a" stroke-width="1.2"/>
        <path d="M12 12v6M8 15h8" stroke="#16a34a" stroke-width="1.2"/>
    </svg>
    @elseif($resolvedTone === 'zip')
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#f1f5f9" stroke="#64748b" stroke-width="1.5"/>
        <path d="M12 6v2M12 10v2M12 14v2" stroke="#64748b" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    @else
    <svg class="files-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#eef2ff" stroke="#6366f1" stroke-width="1.5"/>
        <path d="M14 3v5h5" stroke="#6366f1" stroke-width="1.5"/>
    </svg>
    @endif
</span>