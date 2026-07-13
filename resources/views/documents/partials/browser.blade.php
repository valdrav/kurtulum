@php
    $currentSlug = $currentFolderSlug ?? null;
    $folderSlugFn = fn ($f) => ($f->folder_name === __('documents.default_folder') || $f->folder_name === '') ? '__default' : $f->folder_name;
    $viewMode = request('view', 'grid');
    $sort = request('sort', 'date');
    $queryBase = array_filter(['search' => $search ?? request('search'), 'sort' => $sort !== 'date' ? $sort : null, 'view' => $viewMode !== 'grid' ? $viewMode : null]);
    $uploadLabels = [
        'uploading' => __('documents.upload_progress'),
        'done' => __('documents.upload_complete'),
        'failed' => __('documents.upload_failed'),
        'networkError' => __('documents.upload_network_error'),
        'noFiles' => __('documents.upload_no_files'),
        'folderRequired' => __('documents.folder') . ' *',
    ];
    $canManageDocs = can_manage_documents();
    $canDeleteDocs = can_delete_documents();
    $defaultUploadFolder = $folderName ?? '';
@endphp

<div class="files-app files-app--full" data-files-app data-default-folder-label="{{ __('documents.default_folder') }}">
    <section class="files-main">
        <div class="files-toolbar">
            <div class="files-breadcrumb">
                <a href="{{ route('documents.index') }}" class="files-crumb"><i class="ti ti-home-2"></i></a>
                <i class="ti ti-chevron-right files-crumb-sep"></i>
                @if($currentSlug !== null)
                <a href="{{ route('documents.index') }}" class="files-crumb">{{ __('documents.all_folders') }}</a>
                <i class="ti ti-chevron-right files-crumb-sep"></i>
                <span class="files-crumb current">{{ $displayName ?? __('documents.depot') }}</span>
                @else
                <span class="files-crumb current">{{ __('documents.all_folders') }}</span>
                @endif
            </div>

            <div class="files-toolbar-actions">
                <form method="GET" class="files-search">
                    @if($currentSlug !== null)
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" value="{{ $search ?? request('search') }}"
                           placeholder="{{ $currentSlug !== null ? __('app.search') : __('documents.search_folders') }}...">
                </form>

                <div class="btn-group files-view-toggle" role="group">
                    @if($currentSlug !== null)
                    <a href="{{ route('documents.folder', array_merge(['folder' => $currentSlug], array_merge($queryBase, ['view' => 'grid']))) }}"
                       class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_grid') }}"><i class="ti ti-layout-grid"></i></a>
                    <a href="{{ route('documents.folder', array_merge(['folder' => $currentSlug], array_merge($queryBase, ['view' => 'list']))) }}"
                       class="btn btn-sm {{ $viewMode === 'list' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_list') }}"><i class="ti ti-list"></i></a>
                    @else
                    <a href="{{ route('documents.index', array_merge($queryBase, ['view' => 'grid'])) }}"
                       class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_grid') }}"><i class="ti ti-layout-grid"></i></a>
                    <a href="{{ route('documents.index', array_merge($queryBase, ['view' => 'list'])) }}"
                       class="btn btn-sm {{ $viewMode === 'list' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_list') }}"><i class="ti ti-list"></i></a>
                    @endif
                </div>

                @if($currentSlug !== null)
                <div class="dropdown">
                    <button class="btn btn-sm btn-ghost-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="ti ti-arrows-sort me-1"></i>{{ __('documents.sort') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach(['date' => __('documents.sort_date'), 'name' => __('documents.sort_name'), 'size' => __('documents.sort_size')] as $key => $label)
                        <a class="dropdown-item {{ $sort === $key ? 'active' : '' }}"
                           href="{{ route('documents.folder', array_merge(['folder' => $currentSlug], array_filter(['search' => request('search'), 'view' => $viewMode !== 'grid' ? $viewMode : null, 'sort' => $key !== 'date' ? $key : null]))) }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($canManageDocs)
                <button type="button" class="btn btn-primary files-upload-btn-prominent" data-files-upload-open
                        data-folder="{{ $defaultUploadFolder }}"
                        @if($currentSlug === null) data-files-upload-pick="1" @endif
                        data-bs-toggle="modal" data-bs-target="#filesUploadModal">
                    <i class="ti ti-cloud-upload me-1"></i>
                    {{ $currentSlug !== null ? __('documents.upload_to_this_folder') : __('documents.upload_open_panel') }}
                </button>
                @endif

                @if($currentSlug !== null && $canDeleteDocs)
                <form action="{{ route('documents.folder.destroy', $currentSlug) }}" method="POST" class="d-inline"
                      data-confirm="{{ __('documents.delete_folder_confirm') }}"
                      data-confirm-title="{{ __('app.confirm_title') }}"
                      data-confirm-button="{{ __('documents.delete_folder') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('documents.delete_folder') }}">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>

        @if($canManageDocs)
        <div class="files-create-banner">
            <div class="files-create-banner-icon">@include('documents.partials.icon', ['kind' => 'folder'])</div>
            <div class="files-create-banner-body">
                <div class="fw-semibold">{{ __('documents.create_banner_title') }}</div>
                <div class="text-muted small">{{ __('documents.create_banner_hint') }}</div>
            </div>
            <button type="button" class="btn btn-primary files-upload-btn-prominent" data-files-upload-open
                    data-folder="{{ $defaultUploadFolder }}"
                    @if($currentSlug === null) data-files-upload-pick="1" @endif
                    data-bs-toggle="modal" data-bs-target="#filesUploadModal">
                <i class="ti ti-plus me-1"></i>{{ $currentSlug !== null ? __('documents.upload_to_this_folder') : __('documents.create_new_folder') }}
            </button>
        </div>
        @endif

        <div class="files-content {{ $viewMode === 'list' ? 'is-list' : 'is-grid' }}">
            @if($currentSlug === null)
                @if($viewMode === 'list')
                <div class="files-list files-list-folders">
                    <div class="files-list-head">
                        <span>{{ __('documents.folder') }}</span>
                        <span>{{ __('documents.file_count') }}</span>
                        <span></span>
                    </div>
                    @forelse($folders as $f)
                    @php
                        $slug = $folderSlugFn($f);
                        $folderValue = ($f->folder_name === __('documents.default_folder') || $f->folder_name === '') ? '' : $f->folder_name;
                    @endphp
                    <div class="files-list-row files-list-row-folder">
                        <a href="{{ route('documents.folder', $slug) }}" class="files-list-main">
                            @include('documents.partials.icon', ['kind' => 'folder'])
                            <span class="files-list-name">{{ $f->folder_name }}</span>
                        </a>
                        <span class="files-list-meta">{{ $f->file_count }}</span>
                        <span class="files-list-actions">
                            @if($canManageDocs)
                            <button type="button" class="btn btn-sm btn-primary" data-files-upload-open
                                    data-folder="{{ $folderValue }}" data-bs-toggle="modal" data-bs-target="#filesUploadModal">
                                <i class="ti ti-upload me-1"></i>{{ __('documents.upload_short') }}
                            </button>
                            @if($canDeleteDocs)
                            <form action="{{ route('documents.folder.destroy', $slug) }}" method="POST" class="d-inline ms-1"
                                  data-confirm="{{ __('documents.delete_folder_confirm') }}"
                                  data-confirm-title="{{ __('app.confirm_title') }}"
                                  data-confirm-button="{{ __('documents.delete_folder') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('documents.delete_folder') }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                            @endif
                            @endif
                        </span>
                    </div>
                    @empty
                    <div class="files-empty">{{ __('documents.no_folders') }}</div>
                    @endforelse
                </div>
                @else
                <div class="files-grid">
                    @forelse($folders as $f)
                    @php
                        $slug = $folderSlugFn($f);
                        $folderValue = ($f->folder_name === __('documents.default_folder') || $f->folder_name === '') ? '' : $f->folder_name;
                    @endphp
                    <div class="files-item files-item-folder">
                        <a href="{{ route('documents.folder', $slug) }}" class="files-item-link">
                            @include('documents.partials.icon', ['kind' => 'folder'])
                            <span class="files-item-name">{{ $f->folder_name }}</span>
                            <span class="files-item-meta">{{ $f->file_count }} {{ __('documents.file_count') }}</span>
                        </a>
                        @if($canManageDocs)
                        <div class="files-item-menu files-item-menu--visible">
                            <button type="button" class="btn btn-sm btn-primary w-100" data-files-upload-open
                                    data-folder="{{ $folderValue }}" data-bs-toggle="modal" data-bs-target="#filesUploadModal">
                                <i class="ti ti-upload me-1"></i>{{ __('documents.upload_to_this_folder') }}
                            </button>
                            @if($canDeleteDocs)
                            <form action="{{ route('documents.folder.destroy', $slug) }}" method="POST" class="mt-1"
                                  data-confirm="{{ __('documents.delete_folder_confirm') }}"
                                  data-confirm-title="{{ __('app.confirm_title') }}"
                                  data-confirm-button="{{ __('documents.delete_folder') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger w-100" title="{{ __('documents.delete_folder') }}">
                                    <i class="ti ti-trash me-1"></i>{{ __('documents.delete_folder') }}
                                </button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="files-empty">{{ __('documents.no_folders') }}</div>
                    @endforelse
                </div>
                @endif
            @else
                @if($viewMode === 'list')
                <div class="files-list">
                    <div class="files-list-head">
                        <span>{{ __('documents.file_name') }}</span>
                        <span>{{ __('app.date') }}</span>
                        <span>{{ __('documents.file_size') }}</span>
                        <span></span>
                    </div>
                    @forelse($documents as $d)
                    <div class="files-list-row">
                        <a href="{{ $d->openUrl() }}" class="files-list-main" @if($d->isPreviewable()) target="_blank" @endif>
                            @include('documents.partials.icon', ['document' => $d])
                            <span class="files-list-name">{{ $d->original_name }}</span>
                        </a>
                        <span class="files-list-meta">{{ $d->created_at?->format('d.m.Y') }}</span>
                        <span class="files-list-meta">{{ $d->humanSize() }}</span>
                        <span class="files-list-actions">
                            <a href="{{ route('documents.download', $d) }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.download') }}"><i class="ti ti-download"></i></a>
                            @if($canDeleteDocs)
                            <form method="POST" action="{{ route('documents.destroy', $d) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('app.delete') }}"><i class="ti ti-trash"></i></button>
                            </form>
                            @endif
                        </span>
                    </div>
                    @empty
                    <div class="files-empty">{{ __('app.no_records') }}</div>
                    @endforelse
                </div>
                @else
                <div class="files-grid">
                    @forelse($documents as $d)
                    <div class="files-item files-item-file">
                        <a href="{{ $d->openUrl() }}" class="files-item-link" @if($d->isPreviewable()) target="_blank" @endif>
                            @if($d->iconTone() === 'image' && $d->isPreviewable())
                            <span class="files-thumb" style="background-image:url('{{ route('documents.preview', $d) }}')"></span>
                            @else
                            @include('documents.partials.icon', ['document' => $d])
                            @endif
                            <span class="files-item-name">{{ $d->original_name }}</span>
                            <span class="files-item-meta">{{ $d->humanSize() }}</span>
                        </a>
                        <div class="files-item-menu">
                            <a href="{{ route('documents.download', $d) }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.download') }}"><i class="ti ti-download"></i></a>
                            @if($canDeleteDocs)
                            <form method="POST" action="{{ route('documents.destroy', $d) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('app.delete') }}"><i class="ti ti-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="files-empty">{{ __('app.no_records') }}</div>
                    @endforelse
                </div>
                @endif

                @if(isset($documents) && method_exists($documents, 'links'))
                <div class="files-pagination">{{ $documents->withQueryString()->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</div>

@if($canManageDocs)
<div class="modal fade" id="filesUploadModal" tabindex="-1" aria-labelledby="filesUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filesUploadModalLabel">
                    <i class="ti ti-cloud-upload me-1"></i>{{ __('documents.upload_modal_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('app.close') ?? 'Kapat' }}"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="files-upload-form" data-files-upload-form>
                    @csrf
                    <div class="mb-3" data-files-folder-field>
                        <label class="form-label">{{ __('documents.target_folder') }} *</label>
                        <input type="text" name="folder" class="form-control" maxlength="100" list="folderList"
                               placeholder="{{ __('documents.folder_placeholder') }}" value="{{ old('folder', $defaultUploadFolder) }}"
                               data-files-folder-input>
                        <datalist id="folderList">
                            @foreach($folders as $f)
                            <option value="{{ $f->folder_name === __('documents.default_folder') ? '' : $f->folder_name }}">
                            @endforeach
                        </datalist>
                        <div class="form-hint">{{ __('documents.target_folder_hint') }}</div>
                    </div>
                    <div class="alert alert-azure-lt py-2 mb-3 d-none" data-files-folder-readonly>
                        <i class="ti ti-folder me-1"></i>
                        <span data-files-folder-label></span>
                    </div>
                    <div class="files-dropzone" data-files-dropzone>
                        <input type="file" name="files[]" class="files-dropzone-input" multiple required>
                        <div class="files-dropzone-inner">
                            <i class="ti ti-cloud-upload"></i>
                            <div class="fw-semibold">{{ __('documents.drop_files') }}</div>
                            <div class="text-muted small">{{ __('documents.multi_upload_hint') }}</div>
                            <div class="text-muted small mt-1">{{ __('documents.click_to_select') }}</div>
                        </div>
                    </div>
                    <div class="files-selected-list small text-muted mt-2 d-none" data-files-selected-list></div>
                    <div class="files-upload-progress mt-3" data-files-upload-progress hidden>
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span data-files-upload-label>{{ __('documents.upload_progress') }}</span>
                            <span data-files-upload-percent>0%</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar" role="progressbar" data-files-upload-bar style="width:0%"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-upload me-1"></i>{{ __('app.upload') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>window.__filesUploadLabels = {!! json_encode($uploadLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};</script>
<script src="{{ asset('js/documents-upload.js') }}?v=6"></script>
@endpush
