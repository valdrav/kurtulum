{{-- PDF Düzenleme Stüdyosu --}}
<div class="tab-pane fade show active" id="tab-pdf-editor">
    <div class="alert alert-secondary py-2 small mb-3">
        <i class="ti ti-info-circle me-1"></i>{{ __('documents.tools.editor.intro') }}
    </div>

    <div class="pdf-editor-upload card mb-4">
        <div class="card-body">
            <label class="form-label fw-semibold">{{ __('documents.tools.select_pdf') }}</label>
            <input type="file" id="pdfEditorFile" class="form-control" accept=".pdf,application/pdf">
            <div id="pdfEditorMeta" class="text-muted small mt-2" style="display:none"></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Döndür --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-rotate-2 me-1"></i>{{ __('documents.tools.editor.rotate') }}</h3></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('documents.tools.editor.rotate_help') }}</p>
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="rotate">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.editor.angle') }}</label>
                                <select name="angle" class="form-select">
                                    <option value="90">90°</option>
                                    <option value="180">180°</option>
                                    <option value="270">270°</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.page_range') }}</label>
                                <input type="text" name="pages" class="form-control" placeholder="{{ __('documents.tools.editor.all_pages') }}">
                            </div>
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Filigran --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-droplet me-1"></i>{{ __('documents.tools.editor.watermark') }}</h3></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('documents.tools.editor.watermark_help') }}</p>
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="watermark">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="mb-2">
                            <label class="form-label">{{ __('documents.tools.editor.watermark_text') }}</label>
                            <input type="text" name="text" class="form-control" maxlength="200" placeholder="GİZLİ / TASLAK / Şirket Adı" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.editor.watermark_style') }}</label>
                                <select name="style" class="form-select">
                                    <option value="diagonal">{{ __('documents.tools.editor.style_diagonal') }}</option>
                                    <option value="center">{{ __('documents.tools.editor.style_center') }}</option>
                                    <option value="footer">{{ __('documents.tools.editor.style_footer') }}</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.page_range') }}</label>
                                <input type="text" name="pages" class="form-control" placeholder="{{ __('documents.tools.editor.all_pages') }}">
                            </div>
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Sayfa sil --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-trash me-1"></i>{{ __('documents.tools.editor.remove_pages') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="remove_pages">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="mb-2">
                            <label class="form-label">{{ __('documents.tools.editor.pages_to_remove') }}</label>
                            <input type="text" name="remove_pages" class="form-control" placeholder="2,4,6-8" required>
                            <div class="form-hint">{{ __('documents.tools.page_range_hint') }}</div>
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Sırala --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-arrows-sort me-1"></i>{{ __('documents.tools.editor.reorder') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="reorder">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="mb-2">
                            <label class="form-label">{{ __('documents.tools.editor.page_order') }}</label>
                            <input type="text" name="order" class="form-control" placeholder="3,1,2,5,4" required>
                            <div class="form-hint">{{ __('documents.tools.editor.reorder_hint') }}</div>
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Sayfa numarası --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-list-numbers me-1"></i>{{ __('documents.tools.editor.page_numbers') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="page_numbers">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.editor.number_position') }}</label>
                                <select name="position" class="form-select">
                                    <option value="bottom-center">{{ __('documents.tools.editor.pos_bottom_center') }}</option>
                                    <option value="bottom-right">{{ __('documents.tools.editor.pos_bottom_right') }}</option>
                                    <option value="bottom-left">{{ __('documents.tools.editor.pos_bottom_left') }}</option>
                                    <option value="top-center">{{ __('documents.tools.editor.pos_top_center') }}</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ __('documents.tools.editor.number_format') }}</label>
                                <input type="text" name="format" class="form-control" value="{n} / {total}">
                            </div>
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Üst/Alt bilgi --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-layout-navbar me-1"></i>{{ __('documents.tools.editor.header_footer') }}</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="header_footer">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        <div class="mb-2">
                            <label class="form-label">{{ __('documents.tools.editor.header_text') }}</label>
                            <input type="text" name="header" class="form-control" maxlength="200" placeholder="Kurtulum Ticaret — Fatura">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('documents.tools.editor.footer_text') }}</label>
                            <input type="text" name="footer" class="form-control" maxlength="200" placeholder="www.ornek.com — Gizlidir">
                        </div>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-primary btn-sm" disabled><i class="ti ti-download me-1"></i>{{ __('documents.tools.download_result') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Sıkıştır & A4 --}}
        <div class="col-12 col-lg-6">
            <div class="card pdf-editor-card h-100">
                <div class="card-header"><h3 class="card-title mb-0"><i class="ti ti-file-zip me-1"></i>{{ __('documents.tools.editor.optimize') }}</h3></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('documents.tools.editor.compress_help') }}</p>
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form mb-3">
                        @csrf
                        <input type="hidden" name="operation" value="compress">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-outline-primary btn-sm" disabled><i class="ti ti-file-zip me-1"></i>{{ __('documents.tools.editor.compress') }}</button>
                        @endif
                    </form>
                    <p class="text-muted small">{{ __('documents.tools.editor.fit_a4_help') }}</p>
                    <form method="POST" action="{{ route('documents.tools.pdf-edit') }}" enctype="multipart/form-data" class="pdf-editor-form">
                        @csrf
                        <input type="hidden" name="operation" value="fit_a4">
                        <input type="file" name="file" class="d-none pdf-editor-input" accept=".pdf,application/pdf" required>
                        @if(can_access('documents.create'))
                        <button type="submit" class="btn btn-outline-primary btn-sm" disabled><i class="ti ti-dimensions me-1"></i>{{ __('documents.tools.editor.fit_a4') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
