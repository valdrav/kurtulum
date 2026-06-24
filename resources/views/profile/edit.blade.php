@extends('layouts.app')
@section('title', __('app.profile'))

@section('content')
@include('partials.page-header', ['title' => __('app.profile')])

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="ef-profile-avatar-wrap mx-auto mb-3">
                    @include('partials.user-avatar', ['user' => $user, 'size' => 'xl', 'class' => 'ef-profile-avatar'])
                </div>
                <h3 class="mb-1">{{ $user->name }}</h3>
                <div class="text-muted">{{ $user->email }}</div>
                @if($user->phone)
                <div class="text-muted small mt-1"><i class="ti ti-phone me-1"></i>{{ $user->phone }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.personal_info') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label">{{ __('settings.profile_photo') }}</label>
                        <div class="row align-items-center g-3">
                            <div class="col-auto">
                                @include('partials.user-avatar', ['user' => $user, 'size' => 'lg'])
                            </div>
                            <div class="col">
                                <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                                <div class="form-hint">{{ __('settings.profile_photo_hint') }}</div>
                                @if(user_avatar_url($user))
                                <label class="form-check mt-2">
                                    <input type="checkbox" name="remove_avatar" value="1" class="form-check-input">
                                    <span class="form-check-label text-danger">{{ __('settings.remove_profile_photo') }}</span>
                                </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.profile_name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.profile_email') }}</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.profile_phone') }}</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('settings.profile_locale') }}</label>
                            <select name="locale" class="form-select">
                                @foreach(registry()->languages() as $lang)
                                <option value="{{ $lang->code }}" @selected(old('locale', $user->locale) === $lang->code)>{{ $lang->native_name ?? $lang->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('settings.profile_theme') }}</label>
                            <select name="theme" class="form-select">
                                <option value="light" @selected(old('theme', $user->theme) === 'light')>{{ __('app.light_theme') }}</option>
                                <option value="dark" @selected(old('theme', $user->theme) === 'dark')>{{ __('app.dark_theme') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.profile_new_password') }}</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.profile_password_confirm') }}</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('app.save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
