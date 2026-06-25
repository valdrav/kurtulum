@extends('layouts.app')
@section('title', __('documents.depot'))
@section('content')
@include('partials.page-header', ['title' => __('documents.depot'), 'subtitle' => __('documents.folder_subtitle')])

@if(can_access('documents.create'))
<div class="card mb-4">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('documents.upload_to_folder') }}</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('documents.folder') }} *</label>
                    <input type="text" name="folder" class="form-control" required maxlength="100" list="folderList"
                           placeholder="{{ __('documents.folder_placeholder') }}" value="{{ old('folder') }}">
                    <datalist id="folderList">
                        @foreach($folders as $f)
                        <option value="{{ $f->folder_name === __('documents.default_folder') ? '' : $f->folder_name }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label">{{ __('documents.select_files') }} *</label>
                    <input type="file" name="files[]" class="form-control" multiple required
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp,.csv,.txt,.zip">
                    <div class="form-hint">{{ __('documents.multi_upload_hint') }}</div>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-upload me-1"></i>{{ __('app.upload') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="search" name="search" class="form-control" placeholder="{{ __('documents.search_folders') }}..." value="{{ $search }}">
    </div>
</form>

<div class="doc-folder-grid mb-3">
    @forelse($folders as $f)
    @php
        $slug = ($f->folder_name === __('documents.default_folder') || $f->folder_name === '') ? '__default' : $f->folder_name;
    @endphp
    <a href="{{ route('documents.folder', $slug) }}" class="doc-folder-tile">
        <div class="doc-folder-icon"><i class="ti ti-folder"></i></div>
        <div class="doc-folder-name">{{ $f->folder_name }}</div>
        <div class="text-muted small">{{ $f->file_count }} {{ __('documents.file_count') }}</div>
    </a>
    @empty
    <div class="col-12 text-muted py-4 text-center">{{ __('documents.no_folders') }}</div>
    @endforelse
</div>
@endsection
