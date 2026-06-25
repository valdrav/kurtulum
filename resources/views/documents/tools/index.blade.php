@extends('layouts.app')
@section('title', __('documents.tools.title'))
@section('content')
@include('partials.page-header', [
    'title' => __('documents.tools.title'),
    'subtitle' => __('documents.tools.subtitle'),
])

@if($errors->has('tool'))
<div class="alert alert-danger">{{ $errors->first('tool') }}</div>
@endif

@if(!$officeAvailable)
<div class="alert alert-info d-flex align-items-start gap-2">
    <i class="ti ti-info-circle mt-1"></i>
    <div>
        <strong>{{ __('documents.tools.office_hint_title') }}</strong>
        <p class="mb-0 small">{{ __('documents.tools.office_hint') }}</p>
    </div>
</div>
@endif

<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pdf-editor" type="button"><i class="ti ti-edit me-1"></i>{{ __('documents.tools.editor.title') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-merge" type="button"><i class="ti ti-files me-1"></i>{{ __('documents.tools.merge') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-split" type="button"><i class="ti ti-scissors me-1"></i>{{ __('documents.tools.split') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pdf-word" type="button"><i class="ti ti-file-type-doc me-1"></i>{{ __('documents.tools.pdf_to_word') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-word-pdf" type="button"><i class="ti ti-file-type-pdf me-1"></i>{{ __('documents.tools.word_to_pdf') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-images" type="button"><i class="ti ti-photo me-1"></i>{{ __('documents.tools.images_to_pdf') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-extract" type="button"><i class="ti ti-text-scan-2 me-1"></i>{{ __('documents.tools.extract_text') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-excel" type="button"><i class="ti ti-table me-1"></i>{{ __('documents.tools.excel') }}</button></li>
</ul>

<div class="tab-content">
    @include('documents.tools._pdf-editor')

    {{-- PDF Birleştir --}}
    <div class="tab-pane fade" id="tab-merge">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.merge_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.merge') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('documents.tools.select_pdfs') }}</label>
                        <input type="file" name="files[]" class="form-control" accept=".pdf,application/pdf" multiple required>
                        <div class="form-hint">{{ __('documents.tools.merge_files_hint') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('documents.tools.output_name') }}</label>
                        <input type="text" name="filename" class="form-control" placeholder="birlesik" maxlength="120">
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- PDF Ayır --}}
    <div class="tab-pane fade" id="tab-split">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.split_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.split') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('documents.tools.select_pdf') }}</label>
                        <input type="file" name="file" class="form-control" accept=".pdf,application/pdf" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('documents.tools.split_mode') }}</label>
                        <select name="mode" class="form-select" id="splitMode">
                            <option value="pages">{{ __('documents.tools.split_each_page') }}</option>
                            <option value="range">{{ __('documents.tools.split_range') }}</option>
                        </select>
                    </div>
                    <div class="mb-3" id="splitRangeWrap" style="display:none">
                        <label class="form-label">{{ __('documents.tools.page_range') }}</label>
                        <input type="text" name="range" class="form-control" placeholder="1-3,5,7-9">
                        <div class="form-hint">{{ __('documents.tools.page_range_hint') }}</div>
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- PDF → Word --}}
    <div class="tab-pane fade" id="tab-pdf-word">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.pdf_to_word_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.pdf-to-word') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".pdf,application/pdf" required>
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_docx') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Word → PDF --}}
    <div class="tab-pane fade" id="tab-word-pdf">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.word_to_pdf_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.word-to-pdf') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".doc,.docx" required>
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary" @disabled(!$officeAvailable)><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_pdf') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Görsel → PDF --}}
    <div class="tab-pane fade" id="tab-images">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.images_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.images-to-pdf') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="files[]" class="form-control" accept="image/*" multiple required>
                        <div class="form-hint">{{ __('documents.tools.images_order_hint') }}</div>
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_pdf') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Metin çıkar --}}
    <div class="tab-pane fade" id="tab-extract">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">{{ __('documents.tools.extract_help') }}</p>
                <form method="POST" action="{{ route('documents.tools.extract-text') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" accept=".pdf,application/pdf" required>
                    </div>
                    @if(can_access('documents.create'))
                    <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_txt') }}</button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Excel araçları --}}
    <div class="tab-pane fade" id="tab-excel">
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title mb-0">{{ __('documents.tools.create_excel') }}</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('documents.tools.create_excel_help') }}</p>
                        <form method="POST" action="{{ route('documents.tools.create-excel') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('documents.tools.sheet_name') }}</label>
                                <input type="text" name="sheet_name" class="form-control" value="Sayfa1" maxlength="60">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('documents.tools.table_data') }}</label>
                                <textarea name="rows" class="form-control font-monospace" rows="8" required placeholder="Ad&#9;Tutar&#10;Kalem 1&#9;100&#10;Kalem 2&#9;250"></textarea>
                                <div class="form-hint">{{ __('documents.tools.table_data_hint') }}</div>
                            </div>
                            @if(can_access('documents.create'))
                            <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_xlsx') }}</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title mb-0">{{ __('documents.tools.csv_to_excel') }}</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('documents.tools.csv_to_excel_help') }}</p>
                        <form method="POST" action="{{ route('documents.tools.csv-to-excel') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                            </div>
                            @if(can_access('documents.create'))
                            <button type="submit" class="btn btn-primary"><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_xlsx') }}</button>
                            @endif
                        </form>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title mb-0">{{ __('documents.tools.excel_to_csv') }}</h3></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('documents.tools.excel_to_csv_help') }}</p>
                        <form method="POST" action="{{ route('documents.tools.excel-to-csv') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                            @if(can_access('documents.create'))
                            <button type="submit" class="btn btn-primary" @disabled(!$officeAvailable)><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_csv') }}</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="doc-tools-grid mt-4">
    <div class="doc-tools-item"><i class="ti ti-file-type-pdf"></i><span>PDF</span></div>
    <div class="doc-tools-item"><i class="ti ti-file-type-doc"></i><span>Word</span></div>
    <div class="doc-tools-item"><i class="ti ti-table"></i><span>Excel</span></div>
    <div class="doc-tools-item"><i class="ti ti-file-text"></i><span>CSV / TXT</span></div>
    <div class="doc-tools-item"><i class="ti ti-photo"></i><span>{{ __('documents.tools.images') }}</span></div>
    <div class="doc-tools-item"><i class="ti ti-file-zip"></i><span>ZIP</span></div>
</div>

<p class="text-muted small mt-3 mb-0">
    <a href="{{ route('documents.index') }}"><i class="ti ti-arrow-left me-1"></i>{{ __('documents.tools.back_depot') }}</a>
</p>
@endsection

@push('scripts')
<script>
document.getElementById('splitMode')?.addEventListener('change', function () {
    document.getElementById('splitRangeWrap').style.display = this.value === 'range' ? '' : 'none';
});

(function () {
    const master = document.getElementById('pdfEditorFile');
    const meta = document.getElementById('pdfEditorMeta');
    const forms = document.querySelectorAll('.pdf-editor-form');
    if (!master) return;

    const syncFile = (file) => {
        forms.forEach(form => {
            const input = form.querySelector('.pdf-editor-input');
            const btn = form.querySelector('button[type="submit"]');
            if (!input) return;
            if (file) {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                if (btn) btn.disabled = false;
            } else {
                input.value = '';
                if (btn) btn.disabled = true;
            }
        });
    };

    master.addEventListener('change', async function () {
        const file = this.files?.[0];
        syncFile(file || null);
        if (!file || !meta) return;

        meta.style.display = '';
        meta.textContent = '{{ __('documents.tools.editor.analyzing') }}';

        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', @json(csrf_token()));

        try {
            const res = await fetch(@json(route('documents.tools.pdf-info')), { method: 'POST', body: fd });
            const data = await res.json();
            if (data.pages) {
                meta.textContent = file.name + ' — ' + data.pages + ' {{ __('documents.tools.editor.page_count_label') }}';
            } else {
                meta.textContent = data.error || '';
            }
        } catch (e) {
            meta.textContent = file.name;
        }
    });

    forms.forEach(form => {
        form.addEventListener('submit', function () {
            const masterFile = master.files?.[0];
            if (masterFile) syncFile(masterFile);
        });
    });
})();
</script>
@endpush
