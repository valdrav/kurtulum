@extends('layouts.settings')
@section('settings-title', __('extensions.modules'))
@section('settings-desc', __('extensions.modules_help'))

@section('settings-content')
<div class="row row-cards mb-3">
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Toplam Modül</div>
            <div class="h2 mb-0">{{ $modules->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Aktif</div>
            <div class="h2 mb-0 text-success">{{ $modules->where('is_enabled', true)->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Dosya Sisteminde</div>
            <div class="h2 mb-0">{{ count($discovered) }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <div class="subheader">Pasif</div>
            <div class="h2 mb-0 text-muted">{{ $modules->where('is_enabled', false)->count() }}</div>
        </div></div>
    </div>
</div>

@if(can_access('settings.edit'))
<div class="card mb-3 border-primary">
    <div class="card-header bg-primary-lt">
        <h3 class="card-title mb-0"><i class="ti ti-upload me-1"></i> {{ __('extensions.module_upload') }}</h3>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">{{ __('extensions.module_upload_help') }}</p>
        <form method="POST" action="{{ route('settings.modules.upload') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-8">
                <label class="form-label">ZIP dosyası</label>
                <input type="file" name="module_file" class="form-control" accept=".zip,application/zip" required>
                @error('module_file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-package me-1"></i> {{ __('extensions.module_upload_btn') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter table-modern card-table">
            <thead>
                <tr>
                    <th>Modül</th>
                    <th>Sürüm</th>
                    <th>İzinler</th>
                    <th>Dosya</th>
                    <th>Durum</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($modules as $mod)
                @php($onDisk = isset($discovered[$mod->slug]))
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar bg-purple-lt me-2"><i class="ti ti-puzzle"></i></span>
                            <div>
                                <strong>{{ $mod->name }}</strong>
                                <div class="text-muted small">{{ $mod->slug }}</div>
                                @if($mod->description)<div class="text-muted small">{{ $mod->description }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $mod->version }}</code></td>
                    <td>
                        @forelse($mod->manifest['permissions'] ?? [] as $perm)
                        <span class="badge bg-secondary-lt me-1">{{ permission_label($perm) }}</span>
                        @empty
                        <span class="text-muted">—</span>
                        @endforelse
                    </td>
                    <td>
                        @if($onDisk)
                        <span class="badge bg-success-lt">{{ __('extensions.module_on_disk') }}</span>
                        @else
                        <span class="badge bg-danger-lt">{{ __('extensions.module_missing_files') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($mod->is_enabled)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Pasif</span>@endif
                        @if($mod->is_core)<span class="badge bg-blue-lt ms-1">{{ __('extensions.core_module_badge') }}</span>@endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @if(!$mod->is_core && can_access('settings.edit'))
                            <form method="POST" action="{{ route('settings.modules.toggle', $mod) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-{{ $mod->is_enabled ? 'outline-danger' : 'primary' }}">
                                    {{ $mod->is_enabled ? 'Devre Dışı' : 'Etkinleştir' }}
                                </button>
                            </form>
                            @if($onDisk)
                            <form method="POST" action="{{ route('settings.modules.destroy', $mod) }}" onsubmit="return confirm('Modül dosyaları silinecek. Emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('extensions.module_remove') }}</button>
                            </form>
                            @endif
                            @elseif($mod->is_core)
                            <span class="text-muted small">Sabit</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title mb-0">{{ __('extensions.module_structure_title') }}</h3></div>
    <div class="card-body">
        <p class="text-muted mb-2">{{ __('extensions.module_structure_help') }}</p>
        <pre class="bg-dark text-white p-3 rounded small mb-0">YourModule.zip
  YourModule/
    module.json
    ModuleServiceProvider.php
    Http/Controllers/...
    Resources/views/...</pre>
    </div>
</div>
@endsection
