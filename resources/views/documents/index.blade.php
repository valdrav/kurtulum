@extends('layouts.app')
@section('title', __('documents.depot'))
@section('content')
@include('partials.page-header', ['title' => __('documents.depot')])

<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_access('documents.view'))
    <a href="{{ route('documents.tools.index') }}" class="btn btn-outline-primary">
        <i class="ti ti-file-settings me-1"></i>{{ __('documents.tools.title') }}
    </a>
    @endif
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp">
                </div>
                <div class="col-6 col-md-2">
                    <select name="category" class="form-select">
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ document_category_label($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="text" name="folder" class="form-control" placeholder="{{ __('documents.folder') }}" list="folderList">
                    <datalist id="folderList">@foreach($folders as $f)<option value="{{ $f }}">@endforeach</datalist>
                </div>
                <div class="col-12 col-md-3">
                    <input type="text" name="description" class="form-control" placeholder="{{ __('app.description') }}">
                </div>
                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-upload"></i></button>
                </div>
            </div>
            <div class="mt-2">
                <input type="text" name="tags" class="form-control form-control-sm" placeholder="{{ __('documents.tags_hint') }}">
            </div>
        </form>
    </div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-8 col-md-4">
        <input type="search" name="search" class="form-control" placeholder="{{ __('app.search') }}..." value="{{ request('search') }}">
    </div>
    <div class="col-4 col-md-2">
        <select name="category" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('documents.all_categories') }}</option>
            @foreach($categories as $cat)
            <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ document_category_label($cat) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-2">
        <select name="folder" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('documents.all_folders') }}</option>
            @foreach($folders as $f)
            <option value="{{ $f }}" @selected(request('folder')===$f)>{{ $f }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="doc-grid mb-3">
    @forelse($documents as $d)
    <a href="{{ route('documents.download', $d) }}" class="doc-tile">
        <div class="doc-tile-icon">
            @if(str_contains($d->mime_type ?? '', 'pdf'))
            <i class="ti ti-file-type-pdf"></i>
            @elseif(str_contains($d->mime_type ?? '', 'image'))
            <i class="ti ti-photo"></i>
            @else
            <i class="ti ti-file"></i>
            @endif
        </div>
        <div class="doc-tile-name">{{ $d->original_name }}</div>
        <div class="text-muted small mt-1">{{ $d->humanSize() }}</div>
        @if($d->category)<span class="badge bg-azure-lt mt-1">{{ document_category_label($d->category) }}</span>@endif
        @if($d->folder)<span class="badge bg-secondary-lt mt-1">{{ $d->folder }}</span>@endif
    </a>
    @empty
    <div class="col-12 text-muted py-4 text-center">{{ __('app.no_records') }}</div>
    @endforelse
</div>

<div class="d-flex justify-content-center">{{ $documents->withQueryString()->links() }}</div>
@endsection
