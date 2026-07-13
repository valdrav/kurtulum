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
@endphp

<div class="files-app" data-files-app data-upload-labels="@json($uploadLabels)">
    <aside class="files-sidebar d-none d-lg-flex">
        <div class="files-sidebar-head">
            <i class="ti ti-files"></i>
            <span>{{ __('documents.depot') }}</span>
        </div>
        <nav class="files-sidebar-nav">
            <a href="{{ route('documents.index', $queryBase) }}"
               class="files-sidebar-item {{ $currentSlug === null ? 'active' : '' }}">
                <i class="ti ti-folders"></i>
                <span>{{ __('documents.all_folders') }}</span>
            </a>
            @foreach($folders as $f)
            @php $slug = $folderSlugFn($f); @endphp
            <a href="{{ route('documents.folder', array_merge(['folder' => $slug], $queryBase)) }}"
               class="files-sidebar-item {{ $currentSlug === $slug ? 'active' : '' }}">
                <i class="ti ti-folder{{ $currentSlug === $slug ? '-filled' : '' }}"></i>
                <span class="text-truncate">{{ $f->folder_name }}</span>
                <span class="files-sidebar-badge">{{ $f->file_count }}</span>
            </a>
            @endforeach
        </nav>
        @if(isset($storageStats))
        <div class="files-sidebar-stats">
            <div class="files-sidebar-stats-row">
                <i class="ti ti-database"></i>
                <div>
                    <div class="fw-semibold small">{{ $storageStats['total_human'] }}</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ $storageStats['file_count'] }} {{ __('documents.file_count') }}</div>
                </div>
            </div>
            @if(($storageStats['orphan_count'] ?? 0) > 0)
            <div class="text-warning small mt-1">
                <i class="ti ti-alert-triangle"></i>
                {{ __('documents.orphan_hint', ['count' => $storageStats['orphan_count'], 'size' => $storageStats['orphan_human']]) }}
            </div>
            @endif
            @if(can_access('documents.create'))
            <form method="POST" action="{{ route('documents.purge-orphans') }}" class="mt-2"
                  data-confirm="{{ __('documents.purge_orphans_confirm') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="ti ti-recycle me-1"></i>{{ __('documents.purge_orphans') }}
                </button>
            </form>
            @endif
        </div>
        @endif
    </aside>

    <section class="files-main">
        <div class="files-toolbar">
            <div class="files-breadcrumb">
                <a href="{{ route('documents.index') }}" class="files-crumb"><i class="ti ti-home-2"></i></a>
                @if($currentSlug !== null)
                <i class="ti ti-chevron-right files-crumb-sep"></i>
                <span class="files-crumb current">{{ $displayName ?? __('documents.depot') }}</span>
                @else
                <i class="ti ti-chevron-right files-crumb-sep"></i>
                <span class="files-crumb current">{{ __('documents.all_folders') }}</span>
                @endif
            </div>

            <div class="files-toolbar-actions">
                <select class="form-select form-select-sm files-mobile-folder d-lg-none" onchange="if(this.value) window.location.href=this.value">
                    <option value="{{ route('documents.index') }}" @selected($currentSlug === null)>{{ __('documents.all_folders') }}</option>
                    @foreach($folders as $f)
                    @php $slug = $folderSlugFn($f); @endphp
                    <option value="{{ route('documents.folder', $slug) }}" @selected($currentSlug === $slug)>{{ $f->folder_name }}</option>
                    @endforeach
                </select>

                <form method="GET" class="files-search">
                    @if($currentSlug !== null)
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @endif
                    <i class="ti ti-search"></i>
                    <input type="search" name="search" value="{{ $search ?? request('search') }}"
                           placeholder="{{ $currentSlug !== null ? __('app.search') : __('documents.search_folders') }}...">
                </form>

                @if($currentSlug !== null)
                <div class="btn-group files-view-toggle" role="group">
                    <a href="{{ route('documents.folder', array_merge(['folder' => $currentSlug], array_merge($queryBase, ['view' => 'grid']))) }}"
                       class="btn btn-sm {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_grid') }}"><i class="ti ti-layout-grid"></i></a>
                    <a href="{{ route('documents.folder', array_merge(['folder' => $currentSlug], array_merge($queryBase, ['view' => 'list']))) }}"
                       class="btn btn-sm {{ $viewMode === 'list' ? 'btn-primary' : 'btn-ghost-secondary' }}"
                       title="{{ __('documents.view_list') }}"><i class="ti ti-list"></i></a>
                </div>

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

                @if(can_access('documents.create'))
                <button type="button" class="btn btn-sm btn-primary" data-files-upload-trigger>
                    <i class="ti ti-upload me-1"></i>{{ __('app.upload') }}
                </button>
                @endif

                @if($currentSlug !== null && can_access('documents.create'))
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

        @if(can_access('documents.create'))
        <div class="files-upload-panel" data-files-upload-panel hidden>
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="files-upload-form" data-files-upload-form>
                @csrf
                @if($currentSlug === null)
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('documents.folder') }} *</label>
                        <input type="text" name="folder" class="form-control" required maxlength="100" list="folderList"
                               placeholder="{{ __('documents.folder_placeholder') }}" value="{{ old('folder') }}">
                        <datalist id="folderList">
                            @foreach($folders as $f)
                            <option value="{{ $f->folder_name === __('documents.default_folder') ? '' : $f->folder_name }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>
                @else
                <input type="hidden" name="folder" value="{{ $folderName ?? '' }}">
                @endif
                <div class="files-dropzone" data-files-dropzone>
                    <input type="file" name="files[]" class="files-dropzone-input" multiple required>
                    <div class="files-dropzone-inner">
                        <i class="ti ti-cloud-upload"></i>
                        <div class="fw-semibold">{{ __('documents.drop_files') }}</div>
                        <div class="text-muted small">{{ __('documents.multi_upload_hint') }}</div>
                    </div>
                </div>
                <div class="files-upload-progress mt-3" data-files-upload-progress hidden>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span data-files-upload-label>{{ __('documents.upload_progress') }}</span>
                        <span data-files-upload-percent>0%</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar progress-bar-indeterminate" role="progressbar"
                             data-files-upload-bar style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="button" class="btn btn-ghost-secondary btn-sm" data-files-upload-cancel>{{ __('app.cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-upload me-1"></i>{{ __('app.upload') }}</button>
                </div>
            </form>
        </div>
        @endif

        <div class="files-content {{ $viewMode === 'list' ? 'is-list' : 'is-grid' }}">
            @if($currentSlug === null)
                {{-- Root: folder tiles --}}
                @if($viewMode === 'list')
                <div class="files-list files-list-folders">
                    <div class="files-list-head">
                        <span>{{ __('documents.folder') }}</span>
                        <span>{{ __('documents.file_count') }}</span>
                        <span></span>
                    </div>
                    @forelse($folders as $f)
                    @php $slug = $folderSlugFn($f); @endphp
                    <div class="files-list-row files-list-row-folder">
                        <a href="{{ route('documents.folder', $slug) }}" class="files-list-main">
                            <span class="files-icon files-icon-folder"><i class="ti ti-folder-filled"></i></span>
                            <span class="files-list-name">{{ $f->folder_name }}</span>
                        </a>
                        <span class="files-list-meta">{{ $f->file_count }}</span>
                        <span class="files-list-actions">
                            @if(can_access('documents.create'))
                            <form action="{{ route('documents.folder.destroy', $slug) }}" method="POST" class="d-inline"
                                  data-confirm="{{ __('documents.delete_folder_confirm') }}"
                                  data-confirm-title="{{ __('app.confirm_title') }}"
                                  data-confirm-button="{{ __('documents.delete_folder') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('documents.delete_folder') }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
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
                    @php $slug = $folderSlugFn($f); @endphp
                    <div class="files-item files-item-folder">
                        <a href="{{ route('documents.folder', $slug) }}" class="files-item-link">
                            <span class="files-icon files-icon-folder"><i class="ti ti-folder-filled"></i></span>
                            <span class="files-item-name">{{ $f->folder_name }}</span>
                            <span class="files-item-meta">{{ $f->file_count }} {{ __('documents.file_count') }}</span>
                        </a>
                        @if(can_access('documents.create'))
                        <div class="files-item-menu">
                            <form action="{{ route('documents.folder.destroy', $slug) }}" method="POST"
                                  data-confirm="{{ __('documents.delete_folder_confirm') }}"
                                  data-confirm-title="{{ __('app.confirm_title') }}"
                                  data-confirm-button="{{ __('documents.delete_folder') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-ghost-danger" title="{{ __('documents.delete_folder') }}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="files-empty">{{ __('documents.no_folders') }}</div>
                    @endforelse
                </div>
                @endif
            @else
                {{-- Folder: file items --}}
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
                            <span class="files-icon files-icon-{{ $d->iconTone() }}"><i class="ti {{ $d->iconClass() }}"></i></span>
                            <span class="files-list-name">{{ $d->original_name }}</span>
                        </a>
                        <span class="files-list-meta">{{ $d->created_at?->format('d.m.Y') }}</span>
                        <span class="files-list-meta">{{ $d->humanSize() }}</span>
                        <span class="files-list-actions">
                            <a href="{{ route('documents.download', $d) }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.download') }}"><i class="ti ti-download"></i></a>
                            @if(can_access('documents.create'))
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
                            <span class="files-icon files-icon-{{ $d->iconTone() }}"><i class="ti {{ $d->iconClass() }}"></i></span>
                            @endif
                            <span class="files-item-name">{{ $d->original_name }}</span>
                            <span class="files-item-meta">{{ $d->humanSize() }}</span>
                        </a>
                        <div class="files-item-menu">
                            <a href="{{ route('documents.download', $d) }}" class="btn btn-sm btn-ghost-secondary" title="{{ __('app.download') }}"><i class="ti ti-download"></i></a>
                            @if(can_access('documents.create'))
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

@push('scripts')
<script src="{{ asset('js/documents-upload.js') }}?v=1"></script>
@endpush
