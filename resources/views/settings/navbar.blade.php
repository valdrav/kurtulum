@extends('layouts.settings')
@section('settings-title', __('settings.navbar'))
@section('settings-desc', __('settings.navbar_desc'))

@section('settings-content')
<form method="POST" action="{{ route('settings.navbar.update') }}">
    @csrf @method('PUT')

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#nav-desktop">{{ __('settings.navbar_desktop') }}</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#nav-mobile">{{ __('settings.navbar_mobile') }}</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#nav-topbar">{{ __('settings.navbar_topbar') }}</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="nav-desktop">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_sidebar_style') }}</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('settings.navbar_sidebar_width') }}</label>
                            <input type="text" name="desktop[sidebar_width]" class="form-control" value="{{ old('desktop.sidebar_width', $config['desktop']['sidebar_width']) }}" placeholder="17rem">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('settings.navbar_active_rgb') }}</label>
                            <input type="text" name="desktop[sidebar_active_rgb]" class="form-control" value="{{ old('desktop.sidebar_active_rgb', $config['desktop']['sidebar_active_rgb']) }}" placeholder="99, 102, 241">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('settings.navbar_sidebar_text') }}</label>
                            <input type="color" name="desktop[sidebar_text]" class="form-control form-control-color w-100" value="{{ old('desktop.sidebar_text', $config['desktop']['sidebar_text']) }}">
                        </div>
                        @foreach(['sidebar_bg_start' => __('settings.navbar_bg_start'), 'sidebar_bg_mid' => __('settings.navbar_bg_mid'), 'sidebar_bg_end' => __('settings.navbar_bg_end')] as $field => $label)
                        <div class="col-md-4">
                            <label class="form-label">{{ $label }}</label>
                            <input type="color" name="desktop[{{ $field }}]" class="form-control form-control-color w-100" value="{{ old("desktop.{$field}", $config['desktop'][$field]) }}">
                        </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-check">
                                <input type="hidden" name="desktop[show_brand_text]" value="0">
                                <input type="checkbox" name="desktop[show_brand_text]" value="1" class="form-check-input" @checked(old('desktop.show_brand_text', $config['desktop']['show_brand_text']))>
                                <span class="form-check-label">{{ __('settings.navbar_show_brand_text') }}</span>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check">
                                <input type="hidden" name="desktop[show_user_footer]" value="0">
                                <input type="checkbox" name="desktop[show_user_footer]" value="1" class="form-check-input" @checked(old('desktop.show_user_footer', $config['desktop']['show_user_footer']))>
                                <span class="form-check-label">{{ __('settings.navbar_show_user_footer') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_sidebar_items') }}</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>{{ __('settings.navbar_item') }}</th><th>{{ __('app.status') }}</th></tr></thead>
                        <tbody>
                            @foreach($catalog as $item)
                            <tr>
                                <td><i class="ti {{ $item['icon'] }} me-2"></i>{{ __($item['label']) }}</td>
                                <td>
                                    <input type="hidden" name="desktop_items[{{ $item['id'] }}]" value="0">
                                    <input type="checkbox" name="desktop_items[{{ $item['id'] }}]" value="1" class="form-check-input"
                                           @checked(old("desktop_items.{$item['id']}", $config['desktop']['items'][$item['id']]['enabled'] ?? true))>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="nav-mobile">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_mobile_style') }}</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('settings.navbar_bottom_height') }}</label>
                        <input type="text" name="mobile[bottom_height]" class="form-control" value="{{ old('mobile.bottom_height', $config['mobile']['bottom_height']) }}" placeholder="4.25rem">
                    </div>
                    <div class="col-md-4">
                        <label class="form-check mt-4">
                            <input type="hidden" name="mobile[show_bottom_nav]" value="0">
                            <input type="checkbox" name="mobile[show_bottom_nav]" value="1" class="form-check-input" @checked(old('mobile.show_bottom_nav', $config['mobile']['show_bottom_nav']))>
                            <span class="form-check-label">{{ __('settings.navbar_show_bottom_nav') }}</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-check mt-4">
                            <input type="hidden" name="mobile[show_currency_bar]" value="0">
                            <input type="checkbox" name="mobile[show_currency_bar]" value="1" class="form-check-input" @checked(old('mobile.show_currency_bar', $config['mobile']['show_currency_bar']))>
                            <span class="form-check-label">{{ __('settings.navbar_show_currency_bar') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_bottom_items') }}</h3></div>
                        <div class="card-body">
                            <p class="text-muted small">{{ __('settings.navbar_bottom_hint') }}</p>
                            @foreach($catalog as $item)
                            <label class="form-check mb-2">
                                <input type="checkbox" name="mobile[bottom_items][]" value="{{ $item['id'] }}" class="form-check-input"
                                       @checked(in_array($item['id'], old('mobile.bottom_items', $config['mobile']['bottom_items'] ?? []), true))>
                                <span class="form-check-label"><i class="ti {{ $item['icon'] }} me-1"></i>{{ __($item['label']) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_more_items') }}</h3></div>
                        <div class="card-body">
                            @foreach($catalog as $item)
                            <label class="form-check mb-2">
                                <input type="checkbox" name="mobile[more_items][]" value="{{ $item['id'] }}" class="form-check-input"
                                       @checked(in_array($item['id'], old('mobile.more_items', $config['mobile']['more_items'] ?? []), true))>
                                <span class="form-check-label"><i class="ti {{ $item['icon'] }} me-1"></i>{{ __($item['label']) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="nav-topbar">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('settings.navbar_topbar') }}</h3></div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('settings.navbar_topbar_height') }}</label>
                        <input type="text" name="topbar[height]" class="form-control" value="{{ old('topbar.height', $config['topbar']['height']) }}" placeholder="3.75rem">
                    </div>
                    @foreach([
                        'show_brand_desktop' => __('settings.navbar_show_brand_desktop'),
                        'show_locale' => __('settings.navbar_show_locale'),
                        'show_theme_toggle' => __('settings.navbar_show_theme_toggle'),
                        'show_profile_menu' => __('settings.navbar_show_profile_menu'),
                    ] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-check">
                            <input type="hidden" name="topbar[{{ $field }}]" value="0">
                            <input type="checkbox" name="topbar[{{ $field }}]" value="1" class="form-check-input" @checked(old("topbar.{$field}", $config['topbar'][$field] ?? true))>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
    </div>
</form>
@endsection
