@extends('layouts.settings')
@section('settings-title', __('settings.branding'))
@section('settings-desc', __('settings.branding_desc'))

@section('settings-content')
<form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-text-size me-2"></i>{{ __('settings.branding_text') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.site_short_name') }}</label>
                            <input type="text" name="site_short_name" class="form-control" maxlength="32"
                                   value="{{ old('site_short_name', $settings['site_short_name']) }}"
                                   placeholder="{{ Str::limit(app_brand(), 24, '') }}">
                            <div class="form-hint">{{ __('settings.site_short_name_hint') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.site_theme_color') }}</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="themeColorPicker"
                                       value="{{ old('site_theme_color', $settings['site_theme_color']) }}">
                                <input type="text" name="site_theme_color" class="form-control" maxlength="7"
                                       value="{{ old('site_theme_color', $settings['site_theme_color']) }}"
                                       pattern="^#[0-9A-Fa-f]{6}$" placeholder="#6366f1">
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('settings.site_tagline') }}</label>
                            <input type="text" name="site_tagline" class="form-control" maxlength="160"
                                   value="{{ old('site_tagline', $settings['site_tagline']) }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">{{ __('settings.site_meta_description') }}</label>
                            <textarea name="site_meta_description" class="form-control" rows="2" maxlength="320">{{ old('site_meta_description', $settings['site_meta_description']) }}</textarea>
                        </div>
                        <div class="col-12 mb-0">
                            <label class="form-label">{{ __('settings.site_footer_text') }}</label>
                            <input type="text" name="site_footer_text" class="form-control" maxlength="255"
                                   value="{{ old('site_footer_text', $settings['site_footer_text']) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-photo me-2"></i>{{ __('settings.branding_assets') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach([
                            ['field' => 'logo', 'label' => 'settings.site_logo', 'hint' => 'settings.site_logo_hint', 'accept' => 'image/png,image/jpeg,image/webp,image/svg+xml'],
                            ['field' => 'logo_dark', 'label' => 'settings.site_logo_dark', 'hint' => 'settings.site_logo_dark_hint', 'accept' => 'image/png,image/jpeg,image/webp,image/svg+xml'],
                            ['field' => 'favicon', 'label' => 'settings.site_favicon', 'hint' => 'settings.site_favicon_hint', 'accept' => 'image/png,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/webp'],
                            ['field' => 'apple_icon', 'label' => 'settings.site_apple_icon', 'hint' => 'settings.site_apple_icon_hint', 'accept' => 'image/png,image/jpeg,image/webp'],
                            ['field' => 'pwa_192', 'label' => 'settings.site_pwa_192', 'hint' => 'settings.site_pwa_192_hint', 'accept' => 'image/png,image/jpeg,image/webp'],
                            ['field' => 'pwa_512', 'label' => 'settings.site_pwa_512', 'hint' => 'settings.site_pwa_512_hint', 'accept' => 'image/png,image/jpeg,image/webp'],
                        ] as $asset)
                        <div class="col-md-6">
                            <div class="ef-brand-upload">
                                <label class="form-label">{{ __($asset['label']) }}</label>
                                @if($settings['has_' . $asset['field']])
                                <div class="ef-brand-preview mb-2">
                                    <img src="{{ $settings[$asset['field'] . '_url'] }}" alt="">
                                </div>
                                <label class="form-check mb-2">
                                    <input type="checkbox" name="remove_{{ $asset['field'] }}" value="1" class="form-check-input">
                                    <span class="form-check-label text-danger">{{ __('settings.remove_asset') }}</span>
                                </label>
                                @endif
                                <input type="file" name="{{ $asset['field'] }}" class="form-control" accept="{{ $asset['accept'] }}">
                                <div class="form-hint">{{ __($asset['hint']) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 1rem">
                <div class="card-header">
                    <h3 class="card-title"><i class="ti ti-eye me-2"></i>{{ __('settings.branding_preview') }}</h3>
                </div>
                <div class="card-body">
                    <div class="ef-brand-preview-panel mb-3" style="--ef-primary: {{ old('site_theme_color', $settings['site_theme_color']) }}">
                        <div class="ef-brand-preview-login text-center p-3 rounded mb-3">
                            @if($settings['has_logo'])
                            <img src="{{ $settings['logo_url'] }}" alt="" class="ef-brand-preview-logo mb-2">
                            @else
                            <i class="ti ti-building-warehouse text-primary" style="font-size:2.5rem"></i>
                            @endif
                            <div class="fw-bold">{{ app_brand() }}</div>
                            <div class="text-muted small">{{ old('site_tagline', $settings['site_tagline']) }}</div>
                        </div>
                        <div class="ef-brand-preview-sidebar p-3 rounded">
                            <div class="d-flex align-items-center gap-2">
                                @if($settings['has_logo'])
                                <img src="{{ $settings['logo_url'] }}" alt="" class="ef-brand-preview-icon">
                                @else
                                <span class="avatar bg-primary-lt"><i class="ti ti-building-warehouse"></i></span>
                                @endif
                                <span class="fw-semibold small">{{ Str::limit(app_brand(), 20) }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">{{ __('settings.branding_preview_hint') }}</p>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('app.save') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('themeColorPicker')?.addEventListener('input', function () {
    const input = document.querySelector('input[name="site_theme_color"]');
    if (input) input.value = this.value;
    document.querySelector('.ef-brand-preview-panel')?.style.setProperty('--ef-primary', this.value);
});
document.querySelector('input[name="site_theme_color"]')?.addEventListener('input', function () {
    const picker = document.getElementById('themeColorPicker');
    if (picker && /^#[0-9A-Fa-f]{6}$/.test(this.value)) picker.value = this.value;
});
</script>
@endpush
