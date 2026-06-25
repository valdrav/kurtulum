@extends('layouts.app')
@section('title', $displayName)
@section('content')
@include('partials.page-header', ['title' => $displayName, 'subtitle' => __('documents.folder_files')])

<p class="mb-3"><a href="{{ route('documents.index') }}" class="text-muted"><i class="ti ti-arrow-left me-1"></i>{{ __('documents.back_folders') }}</a></p>

@if(can_access('documents.create'))
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="folder" value="{{ $folderName }}">
            <div class="col-md-8">
                <input type="file" name="files[]" class="form-control" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.csv,.txt,.zip">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-upload me-1"></i>{{ __('documents.add_files') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="search" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}">
    </div>
</form>

<div class="doc-grid mb-3">
    @forelse($documents as $d)
    <div class="doc-tile-wrap">
        @if(str_contains($d->mime_type ?? '', 'pdf'))
        <a href="{{ route('documents.preview', $d) }}" target="_blank" class="doc-tile">
            <div class="doc-tile-icon"><i class="ti ti-file-type-pdf"></i></div>
            <div class="doc-tile-name">{{ $d->original_name }}</div>
            <div class="text-muted small mt-1">{{ $d->humanSize() }}</div>
        </a>
        @elseif(str_contains($d->mime_type ?? '', 'image'))
        <a href="{{ route('documents.preview', $d) }}" target="_blank" class="doc-tile">
            <div class="doc-tile-icon"><i class="ti ti-photo"></i></div>
            <div class="doc-tile-name">{{ $d->original_name }}</div>
            <div class="text-muted small mt-1">{{ $d->humanSize() }}</div>
        </a>
        @else
        <a href="{{ route('documents.download', $d) }}" class="doc-tile">
            <div class="doc-tile-icon"><i class="ti ti-file"></i></div>
            <div class="doc-tile-name">{{ $d->original_name }}</div>
            <div class="text-muted small mt-1">{{ $d->humanSize() }}</div>
        </a>
        @endif
        <div class="doc-tile-actions">
            <a href="{{ route('documents.download', $d) }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.download') }}"><i class="ti ti-download"></i></a>
            @if(can_access('documents.create'))
            <form method="POST" action="{{ route('documents.destroy', $d) }}" class="d-inline" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="col-12 text-muted py-4 text-center">{{ __('app.no_records') }}</div>
    @endforelse
</div>

<div class="d-flex justify-content-center">{{ $documents->withQueryString()->links() }}</div>
@endsection
