@extends('layouts.app')
@section('title', __('documents.tools.studio.title'))
@section('content')
<div class="pdf-studio-page">
    <div class="pdf-studio-topbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('documents.tools.index') }}" class="btn btn-ghost-secondary btn-sm">
                <i class="ti ti-arrow-left"></i>
            </a>
            <h2 class="h4 mb-0">{{ __('documents.tools.studio.title') }}</h2>
            <span class="badge bg-azure-lt">{{ __('documents.tools.studio.client_side') }}</span>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <label class="btn btn-outline-primary btn-sm mb-0">
                <i class="ti ti-upload me-1"></i>{{ __('documents.tools.studio.open_pdf') }}
                <input type="file" id="pdfStudioFile" accept=".pdf,application/pdf" class="d-none">
            </label>
            <button type="button" class="btn btn-primary btn-sm" id="pdfStudioExport" disabled>
                <i class="ti ti-download me-1"></i>{{ __('documents.tools.studio.export') }}
            </button>
        </div>
    </div>

    <div class="pdf-studio-layout" id="pdfStudioApp">
        <aside class="pdf-studio-sidebar" id="pdfStudioThumbs">
            <div class="pdf-studio-sidebar-head">{{ __('documents.tools.studio.pages') }}</div>
            <div class="pdf-studio-thumb-list" id="pdfStudioThumbList"></div>
        </aside>

        <main class="pdf-studio-main">
            <div class="pdf-studio-toolbar" id="pdfStudioToolbar">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary active" data-tool="select" title="{{ __('documents.tools.studio.tool_select') }}"><i class="ti ti-pointer"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="text" title="{{ __('documents.tools.studio.tool_text') }}"><i class="ti ti-typography"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="highlight" title="{{ __('documents.tools.studio.tool_highlight') }}"><i class="ti ti-highlight"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="whiteout" title="{{ __('documents.tools.studio.tool_whiteout') }}"><i class="ti ti-eraser"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="draw" title="{{ __('documents.tools.studio.tool_draw') }}"><i class="ti ti-pencil"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="rect" title="{{ __('documents.tools.studio.tool_rect') }}"><i class="ti ti-square"></i></button>
                    <button type="button" class="btn btn-outline-secondary" data-tool="stamp" title="{{ __('documents.tools.studio.tool_stamp') }}"><i class="ti ti-stamp"></i></button>
                </div>
                <div class="vr mx-1"></div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <input type="color" id="pdfStudioColor" value="#111111" class="form-control form-control-color form-control-sm" title="{{ __('documents.tools.studio.color') }}">
                    <input type="number" id="pdfStudioFontSize" value="14" min="8" max="72" class="form-control form-control-sm pdf-studio-font-input" title="{{ __('documents.tools.studio.font_size') }}">
                    <select id="pdfStudioStamp" class="form-select form-select-sm pdf-studio-stamp-select d-none">
                        <option value="TASLAK">TASLAK</option>
                        <option value="GİZLİ">GİZLİ</option>
                        <option value="ONAYLANDI">ONAYLANDI</option>
                        <option value="KURTULUM">KURTULUM</option>
                    </select>
                </div>
                <div class="vr mx-1"></div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioUndo" disabled><i class="ti ti-arrow-back-up"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioDelete" disabled><i class="ti ti-trash"></i></button>
                </div>
                <div class="vr mx-1"></div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioZoomOut"><i class="ti ti-zoom-out"></i></button>
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioZoomReset">100%</button>
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioZoomIn"><i class="ti ti-zoom-in"></i></button>
                </div>
                <div class="vr mx-1"></div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioPrev"><i class="ti ti-chevron-left"></i></button>
                    <span class="btn btn-outline-secondary disabled pdf-studio-page-indicator" id="pdfStudioPageIndicator">—</span>
                    <button type="button" class="btn btn-outline-secondary" id="pdfStudioNext"><i class="ti ti-chevron-right"></i></button>
                </div>
            </div>

            <div class="pdf-studio-viewport" id="pdfStudioViewport">
                <div class="pdf-studio-empty" id="pdfStudioEmpty">
                    <i class="ti ti-file-type-pdf display-4 text-muted"></i>
                    <p class="text-muted mt-3 mb-0">{{ __('documents.tools.studio.empty_hint') }}</p>
                </div>
                <div class="pdf-studio-stage d-none" id="pdfStudioStage">
                    <div class="pdf-studio-canvas-wrap" id="pdfStudioCanvasWrap">
                        <canvas id="pdfStudioRender"></canvas>
                        <canvas id="pdfStudioFabric"></canvas>
                    </div>
                </div>
            </div>
        </main>

        <aside class="pdf-studio-props">
            <div class="pdf-studio-sidebar-head">{{ __('documents.tools.studio.properties') }}</div>
            <div class="p-3 small text-muted" id="pdfStudioPropsHint">{{ __('documents.tools.studio.props_hint') }}</div>
            <div class="p-3 d-none" id="pdfStudioTextEdit">
                <label class="form-label small">{{ __('documents.tools.studio.edit_text') }}</label>
                <textarea id="pdfStudioTextArea" class="form-control form-control-sm" rows="4"></textarea>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pdf-studio.css') }}">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script>
window.pdfStudioConfig = {
    workerSrc: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js',
    labels: {
        newText: @json(__('documents.tools.studio.new_text')),
    },
};
</script>
<script src="{{ asset('js/pdf-studio.js') }}"></script>
@endpush
