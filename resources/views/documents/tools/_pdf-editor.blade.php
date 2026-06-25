{{-- PDF Düzenleme — görsel stüdyo --}}
<div class="tab-pane fade show active" id="tab-pdf-editor">
    <div class="card pdf-studio-promo">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="avatar avatar-md bg-primary-lt"><i class="ti ti-edit"></i></span>
                        <div>
                            <h3 class="h3 mb-0">{{ __('documents.tools.studio.title') }}</h3>
                            <span class="badge bg-azure-lt mt-1">{{ __('documents.tools.studio.client_side') }}</span>
                        </div>
                    </div>
                    <p class="text-muted mb-4">{{ __('documents.tools.studio.intro') }}</p>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>PDF sayfalarını görüntüleme ve küçük resim gezintisi</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Metin ekleme, seçme ve düzenleme</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-2"></i>Vurgulama, beyazlatma, damga, çizim</li>
                        <li><i class="ti ti-check text-success me-2"></i>Düzenlenmiş PDF'i bilgisayarınıza indirme</li>
                    </ul>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('documents.tools.studio') }}" class="btn btn-primary btn-lg">
                        <i class="ti ti-external-link me-2"></i>{{ __('documents.tools.studio.open_studio') }}
                    </a>
                    <p class="text-muted small mt-3 mb-0">
                        PDF.js + Fabric.js + pdf-lib
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
